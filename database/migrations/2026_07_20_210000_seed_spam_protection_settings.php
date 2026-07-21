<?php

use App\Services\Captcha\CaptchaSettings;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Seed the spam-protection defaults, including the vendors' official public
 * TEST keys, so the feature demonstrates itself the moment an install migrates
 * rather than only once an admin opens the screen.
 *
 * Idempotent: only keys that are absent are written, so a re-run (or a run on a
 * site where staff have already changed something) never overwrites a choice.
 * The safe default is the built-in challenge plus the always-on baseline, which
 * needs no keys at all.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        $existing = DB::table('settings')->pluck('key')->all();
        $now = now();
        $rows = [];

        foreach (CaptchaSettings::DEFAULTS as $key => $value) {
            if (in_array($key, $existing, true)) {
                continue;
            }
            $rows[] = [
                'key' => $key,
                'value' => (string) $value,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($rows) {
            DB::table('settings')->insert($rows);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        DB::table('settings')->whereIn('key', array_keys(CaptchaSettings::DEFAULTS))->delete();
    }
};
