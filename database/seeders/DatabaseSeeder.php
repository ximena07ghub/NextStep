<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // crea el administrador solo cuando se solicita
        if (filter_var(env('SEED_ADMIN', false), FILTER_VALIDATE_BOOLEAN)) {
            $this->call(AdminSeeder::class);
        }
    }
}
