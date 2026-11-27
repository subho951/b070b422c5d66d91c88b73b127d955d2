<?php

namespace App\Support;

use Illuminate\Session\Store;

class LeadAttribution
{
    public const Sources = [
        'gbp' => 'Google Business Profile',
        'google_organic' => 'Google Organic Search',
        'google_ads' => 'Google Ads',
        'facebook' => 'Facebook',
        'instagram' => 'Instagram',
        'direct' => 'Direct Traffic',
        'referral' => 'Other Referrals',
    ];

    public const UtmKeys = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term'];

    public function capture(Store $session, array $query, ?string $referrer, string $siteHost): ?string
    {
        $touch = [];
        foreach (self::UtmKeys as $key) {
            $value = $query[$key] ?? null;
            if (is_string($value) && trim($value) !== '' && mb_strlen($value) <= 255) {
                $touch[$key] = trim($value);
            }
        }

        $host = strtolower((string) parse_url($referrer ?? '', PHP_URL_HOST));
        $isInternal = $host !== '' && preg_replace('/^www\./', '', $host) === preg_replace('/^www\./', '', strtolower($siteHost));
        $hasAdClick = is_string($query['gclid'] ?? null) && trim($query['gclid']) !== ''
            || is_string($query['gbraid'] ?? null) && trim($query['gbraid']) !== ''
            || is_string($query['wbraid'] ?? null) && trim($query['wbraid']) !== '';

        if ($touch === [] && ! $hasAdClick && ($isInternal || $host === '') && $session->has('lead_attribution.first')) {
            return null;
        }

        $source = strtolower($touch['utm_source'] ?? '');
        $medium = strtolower($touch['utm_medium'] ?? '');
        $campaign = strtolower($touch['utm_campaign'] ?? '');
        $google = $source === 'google' || ($source === '' && preg_match('/(?:^|\.)google\.(?:com|[a-z]{2}|(?:com|co)\.[a-z]{2})$/D', $host));
        $touch['source'] = match (true) {
            $source === 'google' && $campaign === 'gbp' => 'gbp',
            $hasAdClick || $google && in_array($medium, ['cpc', 'ppc', 'paid', 'paidsearch', 'paid_search', 'paid-search', 'sem', 'display'], true) => 'google_ads',
            $google => 'google_organic',
            in_array($source, ['facebook', 'fb'], true) || ($source === '' && preg_match('/(?:^|\.)facebook\.com$/D', $host)) => 'facebook',
            in_array($source, ['instagram', 'ig'], true) || ($source === '' && preg_match('/(?:^|\.)instagram\.com$/D', $host)) => 'instagram',
            $touch !== [] || ($host !== '' && ! $isInternal) => 'referral',
            default => 'direct',
        };

        if (! $session->has('lead_attribution.first')) {
            $session->put('lead_attribution.first', $touch);
        }
        $session->put('lead_attribution.last', $touch);

        return $touch['source'];
    }
}
