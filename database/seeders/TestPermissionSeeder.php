<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class TestPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'test@example.com')->first();

        $admin?->assignRole('admin');
    }
}
