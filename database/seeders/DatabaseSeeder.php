<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Book;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Admin Perpustakaan',
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        Book::factory(20)->create();
    }
}
