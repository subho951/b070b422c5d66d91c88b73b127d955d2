<?php

namespace App\Support;

use App\Models\Enquiry;
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

    public function notifyEnquiry(Enquiry $enquiry): void
    {
        try {
            $this->sendEnquiryEmail($enquiry);
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

    private function sendEnquiryEmail(Enquiry $enquiry): void
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
        $subject = 'New Howvanta enquiry from '.str_replace(["\r", "\n"], ' ', $enquiry->name).' <'.$enquiry->email.'>';
        $fields = [
            'Name' => $enquiry->name,
            'Email' => $enquiry->email,
            'Phone' => $enquiry->phone,
            'Company' => $enquiry->company,
            'Country' => $enquiry->country,
            'Intent' => ContactOptions::Intents[$enquiry->intent] ?? $enquiry->intent,
            'Project type' => config('howvanta.services.'.$enquiry->project_type.'.0', $enquiry->project_type),
            'Budget' => $enquiry->budget,
            'Budget currency' => $enquiry->budget_currency,
            'Timeline' => $enquiry->timeline,
            'Privacy consent' => $enquiry->privacy_consent ? 'Given' : 'Not recorded',
        ];
        $lines = ['Enquiry #'.$enquiry->id];
        foreach ($fields as $label => $value) {
            $lines[] = $label.': '.(filled($value) ? $value : 'Not specified');
        }
        $lines[] = "\nMessage:\n".$enquiry->description;
        $lines[] = "\nView enquiry: ".route('admin.enquiries.show', $enquiry);

        Mail::raw(implode("\n", $lines), fn (Message $message) => $message->to($recipient)->subject($subject));
    }
}
