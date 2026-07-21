<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * The staff accounts behind the one-click "Demo Logins" picker on the admin
 * sign-in screen.
 *
 * DemoSeeder already lays down an admin (Marisol Vega, the Town Clerk) and a
 * Parks And Recreation department editor. This adds the two personas that were
 * missing — a site-wide editor and a read-only viewer — so the picker can show
 * every staff experience. Each account is guarded, so running this repeatedly
 * is safe and it will never duplicate a user that already exists.
 *
 * Passwords are obvious on purpose: this is a demonstration install and must
 * never be run against a live town site.
 */
class DemoPersonaSeeder extends Seeder
{
    public function run(): void
    {
        $parks = Department::where('slug', 'parks-and-recreation')->first();

        $personas = [
            [
                'name' => 'Ellis Monroe',
                'email' => 'demo-editor@cottonwoodsprings.example.gov',
                'role' => 'editor',
                'job_title' => 'Communications Editor',
                'department_id' => null,
            ],
            [
                'name' => 'Dana Whitfield',
                'email' => 'demo-viewer@cottonwoodsprings.example.gov',
                'role' => 'viewer',
                'job_title' => 'Records Clerk (Read Only)',
                'department_id' => null,
            ],
            // Belt-and-braces: guarantee the department-editor persona exists
            // even on an install where DemoSeeder never ran. Scoped to Parks.
            [
                'name' => 'Priya Raman',
                'email' => 'parks-editor@cottonwoodsprings.example.gov',
                'role' => 'department_editor',
                'job_title' => 'Parks And Recreation Director',
                'department_id' => $parks?->id,
            ],
        ];

        foreach ($personas as $persona) {
            if (User::where('email', $persona['email'])->exists()) {
                $this->command?->info('Persona already present: ' . $persona['email']);

                continue;
            }

            User::create([
                'name' => $persona['name'],
                'email' => $persona['email'],
                'password' => Hash::make('password'),
                'role' => $persona['role'],
                'department_id' => $persona['department_id'],
                'job_title' => $persona['job_title'],
                'is_active' => true,
                'password_changed_at' => now(),
            ]);

            $this->command?->info('Created demo persona: ' . $persona['email'] . ' (' . $persona['role'] . ')');
        }
    }
}
