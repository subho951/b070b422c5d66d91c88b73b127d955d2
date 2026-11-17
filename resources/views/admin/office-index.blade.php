<x-admin-layout :title="$definition['label']">
<div class="section-heading"><div><p class="eyebrow">BUSINESS MANAGEMENT</p><h1>{{ $definition['label'] }}</h1></div><a class="button" href="{{ route('admin.office.create', $module) }}">Add record +</a></div>
<form method="get" class="office-filters"><div class="field"><label for="search">Search by name</label><input id="search" name="search" value="{{ request('search') }}"></div><button class="button">Search</button><a href="{{ route('admin.office.index', $module) }}">Reset</a></form>
<div class="table-panel"><div class="table-scroll"><table><thead><tr><th>Name</th><th>Details</th><th>Actions</th></tr></thead><tbody>
@forelse($records as $record)<tr><td><strong>{{ $record->name ?? $record->title }}</strong></td><td>{{ $record->email ?? $record->reference ?? ($record->status ?? '') }} @if(isset($record->amount))<span>{{ $record->currency }} {{ number_format($record->amount, 3) }}</span>@endif</td><td><div class="row-actions"><a href="{{ route('admin.office.edit', [$module, $record->id]) }}">Edit</a>
@if($module === 'customers')<a href="{{ route('admin.wallet.show', $record->id) }}">Wallet</a><a href="{{ route('admin.invoices.index', ['customer_id' => $record->id]) }}">Invoices</a>@endif
@if(!in_array($module, ['users','expense-categories']))<form method="post" action="{{ route('admin.office.destroy', [$module,$record->id]) }}" data-confirm="Archive this record?">@csrf @method('DELETE')<button class="text-button danger-link">Archive</button></form>@endif</div></td></tr>
@empty<tr><td colspan="3" class="empty-state">No records found. Add a record to get started.</td></tr>@endforelse
</tbody></table></div>{{ $records->links() }}</div>
</x-admin-layout>