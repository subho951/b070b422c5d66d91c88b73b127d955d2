<x-admin-layout title="Enquiry details">
    <div class="section-heading">
        <div>
            <h1>{{ $enquiry->name }}</h1>
            <p>Enquiry #{{ $enquiry->id }} @if($enquiry->created_at) · Received {{ $enquiry->created_at->format('d M Y, H:i') }} @endif</p>
        </div>
        <a class="button button-secondary" href="{{ route('admin.enquiries.index') }}">All enquiries</a>
    </div>

    <section class="table-panel" aria-labelledby="enquiry-details-heading">
        <div class="table-heading"><h2 id="enquiry-details-heading">Contact and project details</h2></div>
        <div class="table-scroll">
            <table>
                <tbody>
                    <tr><th scope="row">Email</th><td><a href="mailto:{{ $enquiry->email }}">{{ $enquiry->email }}</a></td></tr>
                    @foreach([
                        'Phone' => $enquiry->phone,
                        'Company' => $enquiry->company,
                        'Country' => $enquiry->country,
                        'Intent' => \App\Support\ContactOptions::Intents[$enquiry->intent] ?? $enquiry->intent,
                        'Project type' => config('howvanta.services.'.$enquiry->project_type.'.0', $enquiry->project_type),
                        'Budget' => $enquiry->budget,
                        'Budget currency' => $enquiry->budget_currency,
                        'Timeline' => $enquiry->timeline,
                        'Privacy consent' => $enquiry->privacy_consent ? 'Given' : 'Not recorded',
                    ] as $label => $value)
                        <tr><th scope="row">{{ $label }}</th><td>{{ filled($value) ? $value : '—' }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <section class="form-panel office-form">
        <h2>Enquiry message</h2>
        <p class="enquiry-description preserve-lines">{{ $enquiry->description }}</p>
    </section>

    <form method="post" class="form-panel office-form" action="{{ route('admin.enquiries.update', $enquiry) }}">
        @csrf
        @method('PATCH')
        <h2>Manage enquiry</h2>
        <div class="form-grid">
            <div class="field">
                <label for="enquiry-status">Status</label>
                <select id="enquiry-status" name="status">
                    @foreach(['new', 'contacted', 'qualified', 'converted', 'closed'] as $status)
                        <option value="{{ $status }}" @selected(old('status', $enquiry->status) === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="enquiry-owner">Enquiry owner</label>
                <select id="enquiry-owner" name="assigned_to">
                    <option value="">Unassigned</option>
                    @foreach($enquiryOwners as $owner)
                        <option value="{{ $owner->id }}" @selected((int) old('assigned_to', $enquiry->assigned_to) === (int) $owner->id)>{{ $owner->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="row-actions">
            <input type="hidden" name="consultation_booked" value="0">
            <label><input type="checkbox" name="consultation_booked" value="1" @checked(old('consultation_booked', $enquiry->consultation_booked_at))> Consultation booked</label>
            <input type="hidden" name="proposal_sent" value="0">
            <label><input type="checkbox" name="proposal_sent" value="1" @checked(old('proposal_sent', $enquiry->proposal_sent_at))> Proposal sent</label>
            <button class="button">Save enquiry</button>
        </div>
    </form>
</x-admin-layout>
