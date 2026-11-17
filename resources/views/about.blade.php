<x-layout :title="$pageContent->title" :description="$pageContent->description">
<x-page-intro :page-content="$pageContent"/>
@foreach($website->sections('about') as $sectionRecord)
<x-website-section :section-key="$sectionRecord->key"/>
@endforeach
<x-cta/></x-layout>
