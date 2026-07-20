<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * A bare `db:seed` only lays down the structural defaults — the same ones
     * `municipal:bootstrap` writes. Demonstration content is opt-in via
     * DemoSeeder, because a real municipality's first install should not
     * quietly acquire a fictional mayor.
     */
    public function run(): void
    {
        $this->command->call('municipal:bootstrap');
    }
}
