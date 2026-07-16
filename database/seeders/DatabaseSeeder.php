<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Deliberately factory-free so it runs on production, where dev
     * dependencies (fakerphp/faker) are not installed.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'linards@slmedia.lv'],
            [
                'name' => 'Linards Lazdiņš',
                'password' => Hash::make(config()->string('seed.admin_password')),
                'email_verified_at' => now(),
            ],
        );

        $this->call(CategorySeeder::class);
    }
}
