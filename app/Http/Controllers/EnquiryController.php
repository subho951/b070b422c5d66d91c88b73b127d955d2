<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEnquiryRequest;
use App\Models\Enquiry;
use App\Support\CommunicationSettings;
use App\Support\ContactOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class EnquiryController extends Controller
{
    public function store(StoreEnquiryRequest $request): RedirectResponse
    {
        $data = $request->safe()->except('website');
        $data['privacy_consent'] = true;
        foreach (['company', 'country', 'project_type'] as $optionalField) {
            $data[$optionalField] ??= '';
        }
        $data['budget_currency'] = ContactOptions::currency($data['country']);
        if (! empty($data['budget'])) {
            $data['budget'] = ContactOptions::budgets($data['country'])[$data['budget']];
        }
        $session = $request->session();
        if ($session->get('analytics_consent') === true) {
            $data['first_touch_attribution'] = $session->get('lead_attribution.first');
            $data['last_touch_attribution'] = $session->get('lead_attribution.last');
            $data['lead_source'] = $session->get('lead_attribution.last.source');
        }
        $enquiry = Enquiry::create($data);
        app(CommunicationSettings::class)->notifyEnquiry($enquiry);

        return redirect()->route('contact.thanks')->with([
            'success' => 'Thank you. Your project enquiry has been received. Our team will be in touch.',
            'analytics_conversion' => $session->get('analytics_consent') === true
                ? ($enquiry->intent === 'consultation' ? 'consultation' : 'enquiry')
                : null,
        ]);
    }

    public function thanks(Request $request): Response
    {
        $conversion = $request->session()->pull('analytics_conversion');

        return response()->view('contact-thanks', ['conversion' => $conversion])->header('Cache-Control', 'private, no-store');
    }
}
