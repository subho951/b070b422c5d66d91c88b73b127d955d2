<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrNew(['email' => 'admin@gmail.com']);
        if (! $admin->exists) {
            $admin->name = 'Howvanta Administrator';
            $admin->password = '12345678';
        }
        $admin->role = 'administrator';
        $admin->save();
    }
}
