<?php

namespace Database\Seeders;

use App\Models\Agent;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Agent::factory(10)->create();

        Agent::factory()->create([
            'full_name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }
}
