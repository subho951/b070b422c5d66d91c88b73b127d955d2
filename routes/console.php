<?php

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

Artisan::command('howvanta:admin', function () {
    $name = $this->ask('Administrator name');
    $email = $this->ask('Administrator email');
    $password = $this->secret('Password (minimum 12 characters)');
    $validator = Validator::make(compact('name', 'email', 'password'), ['name' => 'required|string|max:255', 'email' => 'required|email|max:255|unique:users,email', 'password' => 'required|string|min:12']);
    if ($validator->fails()) {
        foreach ($validator->errors()->all() as $error) {
            $this->error($error);
        }

        return 1;
    }
    $user = new User;
    $user->name = $name;
    $user->email = $email;
    $user->password = Hash::make($password);
    $user->role = 'administrator';
    $user->save();
    $this->info('Administrator created.');
})->purpose('Securely create an administrator without stored credentials');

Artisan::command('howvanta:password', function () {
    $email = $this->ask('Account email');
    $user = User::where('email', $email)->first();
    if (! $user) {
        $this->error('Account not found.');

        return 1;
    }
    $password = $this->secret('New password (minimum 12 characters)');
    if (! is_string($password) || mb_strlen($password) < 12) {
        $this->error('Use at least 12 characters.');

        return 1;
    }
    $user->password = Hash::make($password);
    $user->remember_token = Str::random(60);
    $user->save();
    DB::table('sessions')->where('user_id', $user->id)->delete();
    $this->info('Password updated. Database sessions revoked. For file sessions, clear storage/framework/sessions before reopening access.');
})->purpose('Reset an account password through trusted server access');
