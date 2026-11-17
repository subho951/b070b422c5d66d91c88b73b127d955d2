<?php

namespace App\Support;

use Illuminate\Http\Request;

class Recaptcha
{
    public static function isEnabled(Request $request): bool
    {
        if (! config('services.recaptcha.enabled')) {
            return false;
        }

        $isLocalHost = in_array(strtolower(trim($request->getHost(), '[]')), ['localhost', '127.0.0.1', '::1'], true);

        return ! (app()->environment('local') && $isLocalHost);
    }
}
