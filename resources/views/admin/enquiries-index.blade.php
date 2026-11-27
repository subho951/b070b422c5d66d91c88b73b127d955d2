<x-admin-layout title="Enquiries">
    <div class="section-heading">
        <div><h1>Enquiries</h1><p>Review submissions from the Contact Us form.</p></div>
    </div>

    <section class="table-panel" aria-labelledby="enquiries-heading">
        <div class="table-heading"><h2 id="enquiries-heading">All enquiries</h2><span>{{ $enquiries->total() }} total</span></div>
        @include('admin.enquiries-table')
        {{ $enquiries->links() }}
    </section>
</x-admin-layout>
