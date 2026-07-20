<?php

use App\Models\FileItem;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/*
| Unified File Manager, part 2 of 3: data.
|
| Copies document_categories -> folders and documents + media_items -> files.
|
| THE IMPORTANT PROPERTY: documents.id is carried across verbatim as files.id,
| and document_categories.id as folders.id. Five other tables hold foreign keys
| pointing at documents.id (notices, meetings x3, job_postings, bids). Because
| the ids survive, those columns keep resolving to the same file after part 3
| repoints their constraints — no data rewrite, no chance of a mismatched
| agenda attached to the wrong meeting.
|
| Writes go through the query builder, not Eloquent, on purpose: model events
| would regenerate slugs (breaking deep links residents have) and would write
| an audit-log row per document.
|
| NOTHING IS DROPPED. document_categories, documents, and media_items are left
| exactly as they are. See down() for the rollback.
*/
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('folders') || ! Schema::hasTable('files')) {
            return;
        }

        $now = now();
        $usedSlugs = collect(DB::table('files')->pluck('slug'))->flip();

        /* ---------------------------------------------------------------
         | 1. document_categories -> folders (ids preserved, flat -> roots)
         * ------------------------------------------------------------- */
        if (Schema::hasTable('document_categories')) {
            $existingFolderIds = DB::table('folders')->pluck('id')->flip();
            $folderSlugs = collect(DB::table('folders')->pluck('slug'))->flip();
            $rows = [];

            foreach (DB::table('document_categories')->orderBy('id')->get() as $cat) {
                if ($existingFolderIds->has($cat->id)) {
                    continue;   // already migrated
                }
                $slug = $this->uniqueSlug($cat->slug ?: Str::slug($cat->name), $folderSlugs);
                $rows[] = [
                    'id' => $cat->id,
                    'parent_id' => null,
                    'name' => $cat->name,
                    'slug' => $slug,
                    'description' => $cat->description,
                    'icon' => $cat->icon ?: 'folder',
                    'sort_order' => (int) ($cat->sort_order ?? 0),
                    'is_public' => 1,
                    'created_at' => $cat->created_at ?? $now,
                    'updated_at' => $cat->updated_at ?? $now,
                ];
            }
            foreach (array_chunk($rows, 100) as $chunk) {
                DB::table('folders')->insert($chunk);
            }
        }

        /* ---------------------------------------------------------------
         | 2. A home for the old flat media library.
         * ------------------------------------------------------------- */
        $mediaFolderId = null;
        if (Schema::hasTable('media_items') && DB::table('media_items')->exists()) {
            $mediaFolder = DB::table('folders')->where('slug', 'media-library')->first();
            $mediaFolderId = $mediaFolder->id ?? DB::table('folders')->insertGetId([
                'parent_id' => null,
                'name' => 'Media Library',
                'slug' => 'media-library',
                'description' => 'Images and graphics used across the public site.',
                'icon' => 'folder',
                'sort_order' => 900,
                'is_public' => 0,   // staff working area, not a resident-facing folder
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        /* ---------------------------------------------------------------
         | 3. documents -> files (ids preserved, download counts preserved)
         * ------------------------------------------------------------- */
        if (Schema::hasTable('documents')) {
            $existingFileIds = DB::table('files')->pluck('id')->flip();
            $rows = [];

            foreach (DB::table('documents')->orderBy('id')->get() as $doc) {
                if ($existingFileIds->has($doc->id)) {
                    continue;
                }
                // The slug is carried across untouched: /documents/{slug} links
                // printed on agendas and mailers must keep resolving.
                $slug = $this->uniqueSlug($doc->slug, $usedSlugs);
                $rows[] = [
                    'id' => $doc->id,
                    'folder_id' => $doc->document_category_id,
                    'department_id' => $doc->department_id,
                    'title' => $doc->title,
                    'slug' => $slug,
                    'description' => $doc->description,
                    'keywords' => $doc->keywords,
                    'reference' => $doc->reference,
                    'path' => $doc->file_path,
                    'file_name' => $doc->file_name,
                    'mime_type' => $doc->mime_type,
                    'size' => (int) ($doc->file_size ?? 0),
                    'width' => null,
                    'height' => null,
                    'alt_text' => null,
                    'document_date' => $doc->document_date,
                    'download_count' => (int) ($doc->download_count ?? 0),
                    'is_published' => (int) ($doc->is_published ?? 1),
                    'visibility' => FileItem::VISIBILITY_PUBLIC,
                    'kind' => FileItem::kindFor($doc->mime_type, $doc->file_name),
                    'uploaded_by' => $doc->uploaded_by,
                    'created_at' => $doc->created_at ?? $now,
                    'updated_at' => $doc->updated_at ?? $now,
                ];
            }
            foreach (array_chunk($rows, 100) as $chunk) {
                DB::table('files')->insert($chunk);
            }
        }

        /* ---------------------------------------------------------------
         | 4. media_items -> files (new ids; matched on path for re-runs)
         * ------------------------------------------------------------- */
        if (Schema::hasTable('media_items') && $mediaFolderId) {
            $existingPaths = collect(DB::table('files')->pluck('path'))->flip();
            $rows = [];

            foreach (DB::table('media_items')->orderBy('id')->get() as $item) {
                if ($existingPaths->has($item->path)) {
                    continue;
                }
                $base = pathinfo((string) $item->name, PATHINFO_FILENAME) ?: 'file';
                $slug = $this->uniqueSlug(Str::slug($base), $usedSlugs);
                $rows[] = [
                    'folder_id' => $mediaFolderId,
                    'department_id' => null,
                    'title' => Str::headline($base),
                    'slug' => $slug,
                    'description' => null,
                    'keywords' => null,
                    'reference' => null,
                    'path' => $item->path,
                    'file_name' => $item->name,
                    'mime_type' => $item->mime_type,
                    'size' => (int) ($item->size ?? 0),
                    'width' => $item->width,
                    'height' => $item->height,
                    'alt_text' => $item->alt_text,
                    'document_date' => null,
                    'download_count' => 0,
                    'is_published' => 1,
                    'visibility' => FileItem::VISIBILITY_PUBLIC,
                    'kind' => FileItem::kindFor($item->mime_type, $item->name),
                    'uploaded_by' => $item->uploaded_by,
                    'created_at' => $item->created_at ?? $now,
                    'updated_at' => $item->updated_at ?? $now,
                ];
                $existingPaths->put($item->path, true);
            }
            foreach (array_chunk($rows, 100) as $chunk) {
                DB::table('files')->insert($chunk);
            }
        }

        /* ---------------------------------------------------------------
         | 5. Make sure new rows never collide with a preserved id.
         | Inserting explicit ids leaves MySQL's AUTO_INCREMENT correct, but
         | be explicit rather than rely on it.
         * ------------------------------------------------------------- */
        $this->bumpAutoIncrement('files');
        $this->bumpAutoIncrement('folders');
    }

    /**
     * Rollback: empties the new tables but leaves the legacy data untouched
     * (it was never modified). Running up() again re-copies everything.
     */
    public function down(): void
    {
        if (Schema::hasTable('files')) {
            DB::table('files')->delete();
        }
        if (Schema::hasTable('folders')) {
            DB::table('folders')->delete();
        }
    }

    /** Slug uniqueness against a running set, without hitting the DB per row. */
    private function uniqueSlug(?string $desired, \Illuminate\Support\Collection $used): string
    {
        $base = Str::slug((string) $desired) ?: Str::lower(Str::random(8));
        $slug = $base;
        $n = 2;
        while ($used->has($slug)) {
            $slug = $base . '-' . $n++;
        }
        $used->put($slug, true);

        return $slug;
    }

    private function bumpAutoIncrement(string $table): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;   // SQLite/Postgres handle this differently; not needed for the fleet
        }
        $max = (int) DB::table($table)->max('id');
        if ($max > 0) {
            DB::statement("ALTER TABLE `{$table}` AUTO_INCREMENT = " . ($max + 1));
        }
    }
};
