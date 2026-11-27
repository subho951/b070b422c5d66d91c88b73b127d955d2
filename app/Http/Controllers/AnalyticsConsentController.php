<?php

namespace App\Http\Controllers;

use App\Support\LeadAttribution;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnalyticsConsentController extends Controller
{
    public function __invoke(Request $request, LeadAttribution $attribution): JsonResponse
    {
        $data = $request->validate([
            'allowed' => ['required', 'boolean'],
            'campaign' => ['sometimes', 'array:'.implode(',', [...LeadAttribution::UtmKeys, 'gclid', 'gbraid', 'wbraid'])],
            'campaign.*' => ['nullable', 'string', 'max:255'],
            'referrer' => ['nullable', 'string', 'max:512'],
        ]);
        $allowed = $request->boolean('allowed');
        $session = $request->session();
        $session->put('analytics_consent', $allowed);
        $source = null;
        if ($allowed) {
            $source = $attribution->capture($session, $data['campaign'] ?? [], $data['referrer'] ?? null, $request->getHost());
        } else {
            $session->forget('lead_attribution');
        }

        return response()->json([
            'landing_source' => $source,
            'lead_source' => $session->get('lead_attribution.last.source'),
        ]);
    }
}
