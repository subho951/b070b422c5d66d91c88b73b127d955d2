<?php

namespace App\Http\Middleware;

use App\Support\Recaptcha;
use Closure;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class VerifyRecaptcha
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethodSafe() || ! Recaptcha::isEnabled($request)) {
            return $next($request);
        }
        $token = $request->input('g-recaptcha-response');
        if (! config('services.recaptcha.site_key') || ! config('services.recaptcha.secret_key') || ! is_string($token) || $token === '' || strlen($token) > 4096) {
            $this->reject();
        }
        $action = match (true) {
            $request->routeIs('enquiries.store') => 'contact',
            $request->routeIs('login.store') => 'login',
            default => 'admin',
        };
        try {
            $response = Http::asForm()->connectTimeout(3)->timeout(8)->post('https://www.google.com/recaptcha/api/siteverify', [
                'secret' => config('services.recaptcha.secret_key'),
                'response' => $token,
            ]);
        } catch (ConnectionException) {
            $this->reject();
        }
        $result = $response->json();
        $timestamp = $result['challenge_ts'] ?? null;
        try {
            $issuedAt = is_string($timestamp) && trim($timestamp) !== '' ? Carbon::parse($timestamp) : null;
        } catch (\Throwable) {
            $issuedAt = null;
        }
        if (! $response->successful() || ! is_array($result)
            || ($result['success'] ?? false) !== true
            || ($result['action'] ?? null) !== $action
            || ! is_numeric($result['score'] ?? null)
            || $result['score'] < max(0, min(1, config('services.recaptcha.minimum_score')))
            || $result['score'] > 1
            || ! in_array($result['hostname'] ?? null, config('services.recaptcha.hostnames'), true)
            || $issuedAt === null || $issuedAt->lt(now()->subMinutes(2)) || $issuedAt->gt(now()->addSeconds(30))) {
            $this->reject();
        }

        return $next($request);
    }

    private function reject(): never
    {
        throw ValidationException::withMessages(['recaptcha' => 'Spam verification could not be completed. Please reload the page and try again.']);
    }
}
