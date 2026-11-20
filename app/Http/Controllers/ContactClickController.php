<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ContactClickController extends Controller
{
    public const Actions = [
        'form_start' => 'Enquiry form starts',
        'booking' => 'Booking link clicks',
        'whatsapp' => 'WhatsApp clicks',
        'email' => 'Email clicks',
        'phone' => 'Phone clicks',
    ];

    public function __invoke(Request $request): Response
    {
        $data = $request->validate(['action' => ['required', 'string', Rule::in(array_keys(self::Actions))]]);
        $counter = ['day' => today()->toDateString(), 'action' => $data['action']];

        DB::table('website_contact_clicks')->insertOrIgnore($counter);
        DB::table('website_contact_clicks')->where($counter)->increment('clicks');

        return response()->noContent();
    }
}
