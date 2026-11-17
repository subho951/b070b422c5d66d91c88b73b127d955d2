@props(['scope'])
@php($legalSections = $website->sections($scope))
<section class="section legal-content-section"><div class="container legal-layout">
    <aside class="legal-sidebar"><nav aria-label="On this page"><p class="eyebrow">ON THIS PAGE</p>@foreach($legalSections as $record)@foreach($website->section($record->key)['items'] ?? [] as $item)<a href="#{{ \Illuminate\Support\Str::slug(str_replace('.', '-', $record->key).'-'.$item['title']) }}">{{ $item['title'] }}</a>@endforeach @endforeach</nav><div class="legal-contact"><strong>Have a question?</strong><p>Contact our team about this policy or the terms of an engagement.</p><a class="text-link" href="{{ route('contact') }}">Contact Howvanta &nearr;</a></div></aside>
    <div class="legal-body">@foreach($legalSections as $sectionRecord)<x-website-section :section-key="$sectionRecord->key"/>@endforeach</div>
</div></section>
