<?php

namespace App\Support;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class GoogleReviews
{
    public function googleUrl(mixed $value): ?string
    {
        if (! is_string($value) || filter_var($value, FILTER_VALIDATE_URL) === false || parse_url($value, PHP_URL_SCHEME) !== 'https') {
            return null;
        }
        $host = strtolower(parse_url($value, PHP_URL_HOST) ?? '');

        return in_array($host, ['google.com', 'www.google.com', 'maps.google.com', 'search.google.com', 'g.page', 'maps.app.goo.gl'], true) ? $value : null;
    }

    /** @return array<string, mixed> */
    public function snapshot(): array
    {
        $settings = app(CommunicationSettings::class);
        $profile = $this->googleUrl($settings->value('google_reviews.profile_url', config('services.google_reviews.profile_url'))) ?? config('services.google_reviews.profile_url');
        $reviewUrl = $this->googleUrl($settings->value('google_reviews.review_url')) ?? $profile;
        $result = ['profile_url' => $profile, 'review_url' => $reviewUrl, 'rating' => null, 'count' => null, 'reviews' => [], 'attributions' => [], 'available' => false];
        $enabled = $settings->value('google_reviews.enabled', config('services.google_reviews.enabled'));
        if (! in_array($enabled, [true, 1, '1'], true)) {
            return $result;
        }
        try {
            $key = $settings->value('google_reviews.api_key', config('services.google_reviews.api_key'));
            $placeId = $settings->value('google_reviews.place_id', config('services.google_reviews.place_id'));
            if (! is_string($key) || trim($key) === '' || ! is_string($placeId) || ! preg_match('/^[A-Za-z0-9_-]{10,255}$/', $placeId)) {
                return $result;
            }
            $response = Http::acceptJson()->withHeaders([
                'X-Goog-Api-Key' => $key,
                'X-Goog-FieldMask' => 'id,displayName,rating,userRatingCount,reviews,googleMapsUri,googleMapsLinks,attributions',
            ])->connectTimeout(2)->timeout(5)->get('https://places.googleapis.com/v1/places/'.rawurlencode($placeId), ['languageCode' => 'en']);
        } catch (ConnectionException|DecryptException) {
            return $result;
        }
        $data = $response->json();
        if (! $response->successful() || ! is_array($data) || ($data['id'] ?? null) !== $placeId) {
            return $result;
        }
        $result['profile_url'] = $this->googleUrl($data['googleMapsLinks']['reviewsUri'] ?? null) ?? $this->googleUrl($data['googleMapsUri'] ?? null) ?? $profile;
        $result['review_url'] = $this->googleUrl($data['googleMapsLinks']['writeAReviewUri'] ?? null) ?? $reviewUrl;
        if (is_numeric($data['rating'] ?? null) && $data['rating'] >= 1 && $data['rating'] <= 5 && is_numeric($data['userRatingCount'] ?? null) && $data['userRatingCount'] > 0) {
            $result['rating'] = (float) $data['rating'];
            $result['count'] = (int) $data['userRatingCount'];
        }
        foreach (is_array($data['reviews'] ?? null) ? array_slice($data['reviews'], 0, 5) : [] as $review) {
            if (! is_array($review)) {
                continue;
            }
            $author = is_array($review['authorAttribution'] ?? null) ? $review['authorAttribution'] : [];
            $source = $this->googleUrl($review['googleMapsUri'] ?? null);
            if (! is_string($author['displayName'] ?? null) || ! $source || ! is_numeric($review['rating'] ?? null) || $review['rating'] < 1 || $review['rating'] > 5) {
                continue;
            }
            $photo = $author['photoUri'] ?? null;
            $photoHost = is_string($photo) ? strtolower(parse_url($photo, PHP_URL_HOST) ?? '') : '';
            $photoAllowed = is_string($photo) && parse_url($photo, PHP_URL_SCHEME) === 'https' && (str_ends_with($photoHost, '.googleusercontent.com') || str_ends_with($photoHost, '.ggpht.com'));
            $text = $review['originalText']['text'] ?? $review['text']['text'] ?? '';
            $result['reviews'][] = [
                'author' => $author['displayName'], 'author_url' => $this->googleUrl($author['uri'] ?? null),
                'photo_url' => $photoAllowed ? $photo : null, 'source_url' => $source,
                'rating' => (float) $review['rating'], 'text' => is_string($text) ? $text : '',
                'date_label' => is_string($review['relativePublishTimeDescription'] ?? null) ? $review['relativePublishTimeDescription'] : '',
            ];
        }
        foreach (is_array($data['attributions'] ?? null) ? $data['attributions'] : [] as $attribution) {
            if (is_array($attribution) && is_string($attribution['provider'] ?? null)) {
                $url = $attribution['providerUri'] ?? null;
                $result['attributions'][] = ['name' => $attribution['provider'], 'url' => is_string($url) && filter_var($url, FILTER_VALIDATE_URL) && parse_url($url, PHP_URL_SCHEME) === 'https' ? $url : null];
            }
        }
        $result['available'] = true;

        return $result;
    }
}
