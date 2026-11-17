<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class WebsiteMaintenance
{
    public function handle(Request $request, Closure $next): Response
    {
        if (DB::table('site_settings')->where('key', 'maintenance_enabled')->value('value') === '1') {
            return response()->view('maintenance', ['message' => DB::table('site_settings')->where('key', 'maintenance_message')->value('value')], 503)->header('Retry-After', '3600')->header('Cache-Control', 'no-store, private');
        }

        return $next($request);
    }
}
