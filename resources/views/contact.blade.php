@php
    $intents = \App\Support\ContactOptions::Intents;
    $selectedIntent = old('intent', request()->query('intent'));
    $selectedService = old('project_type', request()->query('service'));
    $selectedCountry = is_string(old('country')) ? old('country') : null;
    $contactCopy = $website->section('contact.aside');
    $contactSettings = \Illuminate\Support\Facades\DB::table('site_settings')->whereIn('key', ['contact_location', 'contact_email', 'contact_phone'])->pluck('value', 'key');
    $detailsOpen = ($selectedIntent === 'quote') || filled($selectedService) || $errors->hasAny(['intent', 'project_type', 'country', 'company', 'phone', 'budget', 'timeline']);
@endphp
<x-layout :title="$pageContent->title" :description="$pageContent->description">
    <x-page-intro :page-content="$pageContent" label="START A CONVERSATION"/>
    <section class="section section-tight">
        <div class="container contact-grid">
            @if($contactCopy['_visible'])
                <aside>
                    <h2 class="multiline-heading">{{ $contactCopy['title'] }}</h2>
                    <p>{{ $contactCopy['description'] }}</p>
                    <x-media-image :id="$contactCopy['image_id']" image-class="section-image"/>
                    <div class="contact-detail"><span class="eyebrow">{{ $contactCopy['location_label'] }}</span><p>{{ $contactSettings['contact_location'] ?? '' }}</p></div>
                    <div class="contact-detail"><span class="eyebrow">{{ $contactCopy['email_label'] }}</span><a href="mailto:{{ $contactSettings['contact_email'] ?? '' }}">{{ $contactSettings['contact_email'] ?? '' }}</a></div>
                    <div class="contact-detail"><span class="eyebrow">CALL</span><a href="tel:{{ preg_replace('/[^+0-9]/', '', $contactSettings['contact_phone'] ?? '') }}">{{ $contactSettings['contact_phone'] ?? '' }}</a></div>
                    <div class="contact-detail"><span class="eyebrow">ALTERNATE PHONE</span><a href="tel:{{ preg_replace('/[^+0-9]/', '', config('howvanta.alternate_phone')) }}">{{ config('howvanta.alternate_phone') }}</a></div>
                    @foreach(config('howvanta.contact_links') as $label => $link)
                        @if(is_string($link) && str_starts_with($link, 'https://') && filter_var($link, FILTER_VALIDATE_URL))
                            <div class="contact-detail"><a class="text-link" href="{{ $link }}" @if($label === 'Book a Call') data-contact-action="booking" @elseif($label === 'WhatsApp Business') data-contact-action="whatsapp" @endif>{{ $label }} <span aria-hidden="true">&nearr;</span></a></div>
                        @endif
                    @endforeach
                    <div class="international-contact">
                        <h3>What happens next</h3>
                        <ol class="contact-next-steps">
                            <li>We review your goals and the information you share.</li>
                            <li>We reply by email with questions or a time to talk.</li>
                            <li>We agree the next step before any work begins.</li>
                        </ol>
                    </div>
                    <div class="contact-note">{{ $contactCopy['note'] }}</div>
                </aside>
            @endif
            <div class="form-panel" id="enquiry">
                @if($errors->any())
                    <div class="error-message" role="alert"><strong>Please check your enquiry.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                @endif
                <form method="post" action="{{ route('enquiries.store') }}" data-enquiry-form>
                    @csrf
                    <div class="form-grid">
                        <x-field name="name" label="Your name" :required="true" maxlength="120" autocomplete="name"/>
                        <x-field name="email" label="Email" type="email" :required="true" autocomplete="email"/>
                    </div>
                    <div class="field">
                        <label for="description">Tell us about your project <span aria-hidden="true">*</span></label>
                        <textarea name="description" id="description" rows="5" minlength="15" maxlength="10000" required placeholder="What would you like to build or improve?" @error('description') aria-invalid="true" aria-describedby="description-error" @enderror>{{ old('description') }}</textarea>
                        @error('description')<span class="field-error" id="description-error">{{ $message }}</span>@enderror
                    </div>
                    <details class="contact-optional" @if($detailsOpen) open @endif>
                        <summary>Add project and contact details</summary>
                        <div class="form-grid">
                            <div class="field">
                                <label for="intent">What are you looking for? <small>(optional)</small></label>
                                <select name="intent" id="intent" @error('intent') aria-invalid="true" aria-describedby="intent-error" @enderror><option value="">General enquiry</option>@foreach($intents as $value => $label)<option value="{{ $value }}" @selected($selectedIntent === $value)>{{ $label }}</option>@endforeach</select>
                                @error('intent')<span class="field-error" id="intent-error">{{ $message }}</span>@enderror
                            </div>
                            <div class="field">
                                <label for="project_type">Service <small>(required for a quote)</small></label>
                                <select id="project_type" name="project_type" @required($selectedIntent === 'quote') @error('project_type') aria-invalid="true" aria-describedby="service-error" @enderror><option value="">Select a service</option>@foreach($enquiryServices as $service)<option value="{{ $service->slug }}" @selected($selectedService === $service->slug)>{{ $service->title }}</option>@endforeach</select>
                                @error('project_type')<span class="field-error" id="service-error">{{ $message }}</span>@enderror
                            </div>
                            <x-field name="company" label="Company" maxlength="180" autocomplete="organization"/>
                            <x-field name="phone" label="Phone number" type="tel" maxlength="40" autocomplete="tel" placeholder="Include your country calling code"/>
                            <div class="field">
                                <label for="country">Country <small>(optional)</small></label>
                                <label class="sr-only" for="country-search">Search countries</label>
                                <input type="search" id="country-search" placeholder="Search countries" autocomplete="off" aria-controls="country" hidden>
                                <select name="country" id="country" autocomplete="country-name" @error('country') aria-invalid="true" aria-describedby="country-error" @enderror><option value="">Select your country</option>@foreach(config('countries') as $country => $currency)<option value="{{ $country }}" data-currency="{{ $currency }}" @selected($selectedCountry === $country)>{{ $country }}</option>@endforeach</select>
                                @error('country')<span class="field-error" id="country-error">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <details class="contact-optional" @if(old('budget') || old('timeline') || $errors->hasAny(['budget', 'timeline'])) open @endif>
                            <summary>Add budget and timeline</summary>
                            <div class="form-grid">
                                <div class="field">
                                    <label for="budget">Estimated budget <span id="budget-currency">{{ \App\Support\ContactOptions::currency($selectedCountry) }}</span> <small>(optional)</small></label>
                                    <select id="budget" name="budget" data-budget-ranges="{{ json_encode(\App\Support\ContactOptions::budgetRanges()) }}" aria-describedby="budget-help @error('budget') budget-error @enderror" @disabled(!$selectedCountry)><option value="">{{ $selectedCountry ? 'Select an option' : 'Select a country first' }}</option>@foreach(\App\Support\ContactOptions::budgets($selectedCountry) as $value => $label)<option value="{{ $value }}" @selected(old('budget') === $value)>{{ $label }}</option>@endforeach</select>
                                    <small id="budget-help" class="form-footnote">Budget ranges use your country's currency.</small>
                                    @error('budget')<span class="field-error" id="budget-error">{{ $message }}</span>@enderror
                                </div>
                                <div class="field">
                                    <label for="timeline">Expected timeline <small>(optional)</small></label>
                                    <select id="timeline" name="timeline" @error('timeline') aria-invalid="true" aria-describedby="timeline-error" @enderror><option value="">Select an option</option>@foreach(['As soon as possible','1-3 months','3-6 months','Exploring options'] as $timeline)<option value="{{ $timeline }}" @selected(old('timeline') === $timeline)>{{ $timeline }}</option>@endforeach</select>
                                    @error('timeline')<span class="field-error" id="timeline-error">{{ $message }}</span>@enderror
                                </div>
                            </div>
                        </details>
                    </details>
                    <div class="honeypot" aria-hidden="true"><label for="website">Leave this field empty</label><input name="website" id="website" tabindex="-1" autocomplete="off"></div>
                    <label class="checkbox-label"><input type="checkbox" name="privacy_consent" value="1" required @checked(old('privacy_consent'))><span>I agree to the <a href="{{ route('privacy') }}">privacy policy</a> and consent to Howvanta contacting me about this enquiry.</span></label>
                    <button class="button" type="submit">Send enquiry</button>
                    <p class="form-footnote">Fields marked * are required. No commitment, just a conversation.</p>
                </form>
            </div>
        </div>
    </section>
    <section class="section section-tight contact-map-section"><div class="container"><x-location-map/></div></section>
</x-layout>
