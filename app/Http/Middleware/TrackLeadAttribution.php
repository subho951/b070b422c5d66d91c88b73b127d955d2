<?php

namespace App\Http\Middleware;

use App\Support\LeadAttribution;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackLeadAttribution
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('GET') && $request->session()->get('analytics_consent') === true) {
            $request->attributes->set('landing_source', app(LeadAttribution::class)->capture(
                $request->session(), $request->query(), $request->headers->get('referer'), $request->getHost(),
            ));
        }

        $response = $next($request);
        $response->setPrivate();

        return $response;
    }
}
