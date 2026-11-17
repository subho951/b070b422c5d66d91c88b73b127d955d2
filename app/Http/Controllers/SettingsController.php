<?php

namespace App\Http\Controllers;

use App\Support\GoogleReviews;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class SettingsController extends Controller
{
    public function index(Request $request): View
    {
        $tabs = ['general' => 'General settings', 'maintenance' => 'Maintenance', 'password' => 'Change password', 'email' => 'Email settings', 'sms' => 'SMS settings', 'reviews' => 'Google reviews'];
        $tab = is_string($request->query('tab')) && array_key_exists($request->query('tab'), $tabs) ? $request->query('tab') : 'general';
        $rows = DB::table('site_settings')->get();

        return view('admin.settings', [
            'tabs' => $tabs, 'tab' => $tab,
            'settings' => $rows->where('type', '!=', 'encrypted')->pluck('value', 'key'),
            'brandingSettings' => $rows->where('group', 'branding')->keyBy('key'),
            'hasMailPassword' => $rows->contains('key', 'email.password') || filled(config('mail.mailers.smtp.password')),
            'hasGoogleReviewsKey' => $rows->contains('key', 'google_reviews.api_key') || filled(config('services.google_reviews.api_key')),
            'hasSmsKey' => $rows->contains('key', 'sms.authentication_key'),
        ]);
    }

    public function password(Request $request): RedirectResponse
    {
        $data = $request->validate(['current_password' => ['required', 'string', 'current_password:web'], 'password' => ['required', 'string', Password::min(8), 'confirmed', 'different:current_password']]);
        $request->user()->forceFill(['password' => $data['password'], 'remember_token' => Str::random(60)])->save();
        $request->session()->regenerate();

        return to_route('admin.settings', ['tab' => 'password'])->with('success', 'Your password has been changed.');
    }

    public function email(Request $request): RedirectResponse
    {
        $required = $request->boolean('enabled') ? 'required' : 'nullable';
        $hasPassword = DB::table('site_settings')->where('key', 'email.password')->exists() || filled(config('mail.mailers.smtp.password'));
        $data = $request->validate([
            'enabled' => ['required', 'boolean'],
            'host' => [$required, 'string', 'max:255', 'regex:/^[a-zA-Z0-9.:-]+$/'],
            'port' => [$required, 'integer', 'between:1,65535'],
            'scheme' => ['required', Rule::in(['smtp', 'smtps'])],
            'username' => ['nullable', 'string', 'max:255'],
            'smtp_password' => [Rule::requiredIf($request->boolean('enabled') && filled($request->input('username')) && ! $hasPassword), 'nullable', 'string', 'max:1000'],
            'from_address' => [$required, 'email', 'max:255'], 'from_name' => [$required, 'string', 'max:255'],
            'notification_email' => [$required, 'email', 'max:255'],
        ]);
        $password = $data['smtp_password'] ?? null;
        unset($data['smtp_password']);
        if (filled($password)) {
            $data['password'] = $password;
        }
        $this->saveGroup('email', $data, ['password']);

        return to_route('admin.settings', ['tab' => 'email'])->with('success', 'Email settings saved. They will be used for new enquiry notifications.');
    }

    public function sms(Request $request): RedirectResponse
    {
        $hasKey = DB::table('site_settings')->where('key', 'sms.authentication_key')->exists();
        $data = $request->validate([
            'sms_authentication_key' => [Rule::requiredIf(! $hasKey), 'nullable', 'string', 'max:1000'],
            'sender_id' => ['required', 'string', 'max:100'],
            'base_url' => ['required', 'url:https', 'max:2048'],
        ]);
        $key = $data['sms_authentication_key'] ?? null;
        unset($data['sms_authentication_key']);
        if (filled($key)) {
            $data['authentication_key'] = $key;
        }
        $data['enabled'] = '0';
        $this->saveGroup('sms', $data, ['authentication_key']);

        return to_route('admin.settings', ['tab' => 'sms'])->with('success', 'SMS gateway details saved. Sending will be connected when your provider details are available.');
    }

    public function reviews(Request $request, GoogleReviews $reviews): RedirectResponse
    {
        $hasKey = DB::table('site_settings')->where('key', 'google_reviews.api_key')->exists() || filled(config('services.google_reviews.api_key'));
        $googleLink = function (string $attribute, mixed $value, Closure $fail) use ($reviews): void {
            if (! $reviews->googleUrl($value)) {
                $fail('Use an HTTPS Google Business, Google Maps or g.page link.');
            }
        };
        $data = $request->validate([
            'enabled' => ['required', 'boolean'],
            'profile_url' => ['required', 'url:https', 'max:2048', $googleLink],
            'review_url' => ['nullable', 'url:https', 'max:2048', $googleLink],
            'place_id' => [$request->boolean('enabled') ? 'required' : 'nullable', 'string', 'max:255', 'regex:/^[A-Za-z0-9_-]{10,255}$/'],
            'google_api_key' => [Rule::requiredIf($request->boolean('enabled') && ! $hasKey), 'nullable', 'string', 'max:1000'],
        ]);
        $key = $data['google_api_key'] ?? null;
        unset($data['google_api_key']);
        if (filled($key)) {
            $data['api_key'] = $key;
        }
        $this->saveGroup('google_reviews', $data, ['api_key']);

        return to_route('admin.settings', ['tab' => 'reviews'])->with('success', 'Google review settings saved. Profile links are ready; automatic display runs only when enabled with valid credentials.');
    }

    /** @param array<string, mixed> $data @param array<int, string> $secrets */
    private function saveGroup(string $group, array $data, array $secrets): void
    {
        DB::transaction(function () use ($group, $data, $secrets): void {
            foreach ($data as $key => $value) {
                $isSecret = in_array($key, $secrets, true);
                DB::table('site_settings')->updateOrInsert(['key' => $group.'.'.$key], ['group' => $group, 'type' => $isSecret ? 'encrypted' : 'text', 'value' => $isSecret ? Crypt::encryptString($value) : (string) $value, 'updated_at' => now()]);
            }
        });
    }
}
