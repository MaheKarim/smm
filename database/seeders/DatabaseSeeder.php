<?php

namespace Database\Seeders;

use App\Models\User;
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

        User::factory()->create([
            'name' => 'Mahe',
            'email' => 'mahe@momagicbd.com',
            'password' => bcrypt('123456'),
            'email_verified_at' => now(),
            'remember_token' => '123456',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
