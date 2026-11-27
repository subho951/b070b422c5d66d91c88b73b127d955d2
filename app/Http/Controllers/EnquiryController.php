<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEnquiryRequest;
use App\Models\Enquiry;
use App\Support\CommunicationSettings;
use App\Support\ContactOptions;
use Illuminate\Http\RedirectResponse;

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
        $enquiry = Enquiry::create($data);
        app(CommunicationSettings::class)->notifyEnquiry($enquiry);

        return redirect()->route('contact.thanks')->with('success', 'Thank you. Your project enquiry has been received. Our team will be in touch.');
    }
}
