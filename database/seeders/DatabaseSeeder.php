<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            Module1Seeder::class,
            ActeurSeeder::class,
            Module3Seeder::class,
            Module2Seeder::class,
            Module4Seeder::class,
            Module5Seeder::class,
            FrontOfficeSeeder::class,
        ]);
    }
}
