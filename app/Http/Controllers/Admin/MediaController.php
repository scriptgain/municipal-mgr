<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MediaItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaController extends Controller
{
    public function index(Request $request)
    {
        $query = MediaItem::with('uploader')->latest();

        if ($term = trim((string) $request->query('q'))) {
            $like = '%' . $term . '%';
            $query->where(fn ($q) => $q->where('name', 'like', $like)->orWhere('alt_text', 'like', $like));
        }

        return view('admin.media.index', [
            'records' => $query->paginate(36)->withQueryString(),
            'search' => $request->query('q'),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'files' => ['required', 'array', 'max:20'],
            'files.*' => ['file', 'max:20480', 'mimes:jpg,jpeg,png,gif,webp,svg,pdf'],
        ]);

        $n = 0;
        foreach ($request->file('files') as $file) {
            $name = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))
                . '-' . Str::lower(Str::random(6)) . '.' . $file->getClientOriginalExtension();
            $path = Storage::disk('public')->putFileAs('media', $file, $name);

            $size = @getimagesize($file->getRealPath());

            MediaItem::create([
                'name' => $file->getClientOriginalName(),
                'path' => $path,
                'mime_type' => $file->getClientMimeType(),
                'size' => $file->getSize(),
                'width' => $size[0] ?? null,
                'height' => $size[1] ?? null,
                'uploaded_by' => auth()->id(),
            ]);
            $n++;
        }

        return back()->with('status', "{$n} File(s) Uploaded.");
    }

    /**
     * Alt text is the only editable field, and it matters: an image on a
     * government site without alt text is an accessibility complaint waiting
     * to happen.
     */
    public function update(Request $request, MediaItem $mediaItem)
    {
        $data = $request->validate([
            'alt_text' => ['nullable', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
        ]);
        $mediaItem->update($data);

        return back()->with('status', 'Media Details Saved.');
    }

    public function destroy(MediaItem $mediaItem)
    {
        Storage::disk('public')->delete($mediaItem->path);
        $mediaItem->delete();

        return back()->with('status', 'File Deleted.');
    }

    public function bulkDestroy(Request $request)
    {
        $ids = array_filter(array_map('intval', (array) $request->input('ids', [])));
        $items = $ids ? MediaItem::whereIn('id', $ids)->get() : collect();

        foreach ($items as $item) {
            Storage::disk('public')->delete($item->path);
            $item->delete();
        }

        return back()->with('status', $items->count() . ' File(s) Deleted.');
    }
}
