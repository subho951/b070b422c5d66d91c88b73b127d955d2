<?php

namespace App\Support;

use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CommunicationSettings
{
    public function value(string $key, mixed $default = null): mixed
    {
        $record = DB::table('site_settings')->where('key', $key)->first();
        if ($record === null) {
            return $default;
        }

        return $record->type === 'encrypted' ? Crypt::decryptString($record->value) : $record->value;
    }

    public function notifyEnquiry(): void
    {
        try {
            $this->sendEnquiryEmail();
        } catch (\Throwable) {
            Log::warning('Enquiry saved; email notification delivery failed.');
        }

    }

    public function applyMailConfiguration(): void
    {
        config(['mail.default' => 'smtp', 'mail.mailers.smtp' => [
            'transport' => 'smtp', 'scheme' => $this->value('email.scheme', 'smtp'),
            'host' => $this->value('email.host'), 'port' => (int) $this->value('email.port', 587),
            'username' => $this->value('email.username'),
            'password' => $this->value('email.password', config('mail.mailers.smtp.password')),
            'require_tls' => true, 'timeout' => 10,
        ], 'mail.from.address' => $this->value('email.from_address'), 'mail.from.name' => $this->value('email.from_name')]);
        app('mail.manager')->purge('smtp');
    }

    private function sendEnquiryEmail(): void
    {
        $enabled = $this->value('email.enabled');
        if ($enabled !== null && $enabled !== '1') {
            return;
        }
        $recipient = $enabled === '1' ? $this->value('email.notification_email') : config('howvanta.notification_email');
        if (! $recipient) {
            return;
        }
        if ($enabled === '1') {
            $this->applyMailConfiguration();
        }
        Mail::raw('A new enquiry is available in the protected Howvanta dashboard.', fn (Message $message) => $message->to($recipient)->subject('New Howvanta enquiry'));
    }
}
