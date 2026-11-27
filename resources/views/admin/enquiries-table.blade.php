<div class="table-scroll">
    <table>
        <thead><tr><th scope="col">Contact</th><th scope="col">Company / country</th><th scope="col">Project</th><th scope="col">Status</th><th scope="col">Received</th><th scope="col">Actions</th></tr></thead>
        <tbody>
            @forelse($enquiries as $enquiry)
                <tr>
                    <td><strong>{{ $enquiry->name }}</strong><a href="mailto:{{ $enquiry->email }}">{{ $enquiry->email }}</a></td>
                    <td>{{ $enquiry->company ?: '—' }}<span>{{ $enquiry->country }}</span></td>
                    <td>
                        {{ config('howvanta.services.'.$enquiry->project_type.'.0', $enquiry->project_type) }}
                        @if($enquiry->intent)
                            <span>{{ \App\Support\ContactOptions::Intents[$enquiry->intent] ?? $enquiry->intent }}</span>
                        @endif
                    </td>
                    <td>{{ ucfirst($enquiry->status) }}</td>
                    <td>{{ $enquiry->created_at?->format('d M Y') ?? '—' }}</td>
                    <td><a href="{{ route('admin.enquiries.show', $enquiry) }}" aria-label="View enquiry from {{ $enquiry->name }}">View details</a></td>
                </tr>
            @empty
                <tr><td colspan="6" class="empty-state">No enquiries yet. Submissions from the contact page will appear here.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
