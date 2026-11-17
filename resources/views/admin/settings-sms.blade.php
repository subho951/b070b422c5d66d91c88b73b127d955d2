<form method="post" action="{{ route('admin.settings.sms', ['tab' => 'sms']) }}">
    @csrf
    @method('PUT')
    <div class="settings-fields">
        <div>
            <x-field name="sms_authentication_key" label="Authentication Key" type="password" :required="!$hasSmsKey" autocomplete="new-password" maxlength="1000"/>
            @if($hasSmsKey)<p class="settings-help">A key is saved. Leave blank to keep it.</p>@endif
        </div>
        <div class="field"><label for="sender_id">Sender ID</label><input id="sender_id" name="sender_id" value="{{ old('sender_id', $settings['sms.sender_id'] ?? '') }}" maxlength="100" required></div>
        <div class="field"><label for="base_url">Base URL</label><input type="url" id="base_url" name="base_url" value="{{ old('base_url', $settings['sms.base_url'] ?? '') }}" placeholder="https://" maxlength="2048" required></div>
    </div>
    <div class="settings-submit"><button class="button" type="submit">Save SMS settings &rarr;</button></div>
</form>
