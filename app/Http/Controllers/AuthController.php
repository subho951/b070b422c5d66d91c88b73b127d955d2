<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function store(LoginRequest $request): RedirectResponse
    {
        if (! Auth::attempt($request->validated())) {
            throw ValidationException::withMessages(['email' => 'These credentials could not be verified.']);
        }
        $request->session()->regenerate();
        DB::table('activity_logs')->insert([
            'actor_id' => $request->user()->id,
            'actor_name' => $request->user()->name,
            'subject_type' => 'user',
            'subject_id' => $request->user()->id,
            'event' => 'login',
            'description' => 'Successful login from '.$request->ip(),
            'created_at' => now(),
        ]);

        return redirect()->intended(route('admin.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
