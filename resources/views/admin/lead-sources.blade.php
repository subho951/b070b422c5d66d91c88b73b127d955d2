<x-admin-layout title="Lead sources">
    <div class="section-heading">
        <div><h1>Lead sources</h1><p>Filter enquiries by date received and last-touch source.</p></div>
    </div>
    <form class="office-filters" method="get" action="{{ route('admin.enquiries.sources') }}">
        <label class="field">From <input type="date" name="from" value="{{ $filters['from'] ?? '' }}"></label>
        <label class="field">To <input type="date" name="to" value="{{ $filters['to'] ?? '' }}"></label>
        <label class="field">Lead source
            <select name="source">
                <option value="">All sources</option>
                @foreach($sources + ['unattributed' => 'Unattributed'] as $source => $label)
                    <option value="{{ $source }}" @selected(($filters['source'] ?? '') === $source)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <button class="button" type="submit">Apply filters</button>
        <button class="button" type="submit" name="format" value="csv">Export CSV</button>
    </form>

    <div class="metric-grid dashboard-metrics">
        @foreach($metrics as $label => $total)
            <article class="metric-card metric-teal"><div class="metric-top"><span>{{ $label }}</span></div><strong>{{ $total }}</strong></article>
        @endforeach
    </div>

    <section class="table-panel" aria-labelledby="lead-sources-heading">
        <div class="table-heading"><h2 id="lead-sources-heading">Enquiries by source</h2></div>
        <div class="table-scroll">
            <table>
                <thead><tr><th scope="col">Enquiry</th><th scope="col">Received</th><th scope="col">First touch</th><th scope="col">Last touch</th></tr></thead>
                <tbody>
                    @forelse($enquiries as $enquiry)
                        <tr>
                            <td><a href="{{ route('admin.enquiries.show', $enquiry) }}">#{{ $enquiry->id }}</a></td>
                            <td>{{ $enquiry->created_at?->toDateString() }}</td>
                            <td>{{ $sources[$enquiry->first_touch_attribution['source'] ?? ''] ?? 'Unattributed' }}</td>
                            <td>{{ $sources[$enquiry->lead_source] ?? 'Unattributed' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4">No enquiries match these filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $enquiries->links() }}
    </section>
</x-admin-layout>
