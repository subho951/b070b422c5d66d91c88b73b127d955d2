<x-admin-layout title="Settings">
<div class="settings-heading"><p class="eyebrow">WORKSPACE SETTINGS</p><h1>Settings</h1><p>Manage your website, account and notification channels.</p></div>
<div class="form-panel content-form settings-form settings-workspace">
    <nav class="settings-toolbar settings-tabs" aria-label="Settings tabs">
        @foreach($tabs as $key => $label)
            <a href="{{ route('admin.settings', ['tab' => $key]) }}" @if($tab === $key) aria-current="page" @endif>{{ $label }}</a>
        @endforeach
    </nav>
    <section class="settings-panel">
        <h2 class="settings-panel-title">{{ $tabs[$tab] }}</h2>
        <div class="settings-panel-body">
            @include('admin.settings-'.$tab)
        </div>
    </section>
</div>
</x-admin-layout>
