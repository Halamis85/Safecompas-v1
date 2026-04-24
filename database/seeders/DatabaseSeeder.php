<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }
}

/**
 * php artisan tinker
 *
* DB::table('users')->insert([
    * 'name' => 'Lukáš',
    * 'email' => 'halamis@seznam.cz',
    * 'username' => 'lhala',
    * 'firstname' => 'Lukáš',
    * 'lastname' => 'Halamka',
    * 'password' => Hash::make('123456'),
    * 'alias' => 'Lukáši',
    * 'role' => 'admin',
    * 'created_at' => now(),
    * 'updated_at' => now()
* ]);
 *
 * // Ověř, že se vytvořil
 * DB::table('users')->select('id', 'username', 'password')->get();
 *
 * // Výstup z tinkeru
* exit ctrl+c
*/
