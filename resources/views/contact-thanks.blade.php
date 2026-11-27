<x-layout title="Thank you for your enquiry" description="What happens after you send Howvanta Technologies an enquiry." :no-index="true">
    @if($conversion && session('analytics_consent') === true)
        <span data-analytics-conversion="{{ $conversion }}" hidden></span>
    @endif
    <section class="section">
        <div class="container prose">
            <h1>{{ session('success') ? 'Your enquiry has been received' : 'What happens after an enquiry' }}</h1>
            <p>We review your project summary and reply by email. We may ask a few questions or suggest a time to talk so we can agree on a practical next step.</p>
            <p><a class="button" href="{{ route('portfolio') }}">View Case Studies</a></p>
        </div>
    </section>
</x-layout>
