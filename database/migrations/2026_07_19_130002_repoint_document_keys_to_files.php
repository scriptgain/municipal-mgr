<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
| Unified File Manager, part 3 of 3: foreign keys.
|
| Five tables attach a document to a record (a notice's signed PDF, a meeting's
| agenda/minutes/packet, a job's application form, a bid's spec). Their columns
| are left alone — part 2 preserved documents.id as files.id, so every stored
| value still identifies the same file. Only the CONSTRAINT moves, from
| documents(id) to files(id).
|
| The column names keep the word "document" (notices.document_id and friends).
| Renaming them would touch every form, validator, and view for no functional
| gain, and a rename is far harder to roll back than a constraint swap.
*/
return new class extends Migration
{
    /** table => [columns] */
    private array $map = [
        'notices' => ['document_id'],
        'meetings' => ['agenda_document_id', 'minutes_document_id', 'packet_document_id'],
        'job_postings' => ['application_document_id'],
        'bids' => ['document_id'],
    ];

    public function up(): void
    {
        if (! Schema::hasTable('files')) {
            return;
        }

        foreach ($this->map as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            foreach ($columns as $column) {
                if (! Schema::hasColumn($table, $column)) {
                    continue;
                }

                // Any id that did not survive the copy is cleared rather than
                // left to fail the constraint. With ids preserved this should
                // match nothing, but a broken FK is worse than a null agenda.
                DB::table($table)
                    ->whereNotNull($column)
                    ->whereNotIn($column, fn ($q) => $q->select('id')->from('files'))
                    ->update([$column => null]);

                $this->dropForeignIfExists($table, $column);

                Schema::table($table, function (Blueprint $t) use ($column) {
                    $t->foreign($column)->references('id')->on('files')->nullOnDelete();
                });
            }
        }
    }

    /** Points the constraints back at documents. */
    public function down(): void
    {
        if (! Schema::hasTable('documents')) {
            return;
        }

        foreach ($this->map as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            foreach ($columns as $column) {
                if (! Schema::hasColumn($table, $column)) {
                    continue;
                }
                DB::table($table)
                    ->whereNotNull($column)
                    ->whereNotIn($column, fn ($q) => $q->select('id')->from('documents'))
                    ->update([$column => null]);

                $this->dropForeignIfExists($table, $column);

                Schema::table($table, function (Blueprint $t) use ($column) {
                    $t->foreign($column)->references('id')->on('documents')->nullOnDelete();
                });
            }
        }
    }

    /**
     * Drops whatever FK currently sits on a column, by looking the constraint
     * name up rather than assuming Laravel's default naming — these tables
     * were built across several migrations and one is easy to mis-guess.
     */
    private function dropForeignIfExists(string $table, string $column): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        $names = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->select('CONSTRAINT_NAME')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('COLUMN_NAME', $column)
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->pluck('CONSTRAINT_NAME');

        foreach ($names as $name) {
            DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$name}`");
        }
    }
};
