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
        $this->call(RoleSeeder::class);

        // Akun Super Admin awal untuk login pertama saat onboarding klien baru
        // (docs/SETUP.md §4 poin 3). Password wajib diganti segera setelah login pertama.
        $superAdmin = User::factory()->create([
            'name' => 'Super Admin',
            'email' => 'superadmin@simpeg.local',
            'password' => 'password',
        ]);
        $superAdmin->assignRole('Super Admin');
    }
}
