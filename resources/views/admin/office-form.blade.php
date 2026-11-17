<x-admin-layout :title="$definition['label']">
<a class="text-link" href="{{ route('admin.office.index', $module) }}">&larr; Back to {{ strtolower($definition['label']) }}</a><div class="editor-heading"><p class="eyebrow">BUSINESS MANAGEMENT</p><h1>{{ $record ? 'Edit' : 'Add' }} {{ strtolower($definition['label']) }}</h1></div>
<form class="form-panel office-form" method="post" action="{{ $record ? route('admin.office.update', [$module,$record->id]) : route('admin.office.store',$module) }}">@csrf @if($record) @method('PUT') @endif
<div class="form-grid">@foreach($definition['fields'] as $field => $rules)
@php
$value = old($field, $field === 'password' ? '' : ($record->$field ?? match($field) {'country'=>'India','billing_currency','currency'=>'INR','type'=>'company','status'=>$module === 'projects' ? 'planned' : 'active','priority'=>'normal','role'=>'staff','is_active'=>1,'sort_order'=>0,default=>''}));
$options = null;
foreach(explode('|',$rules) as $rule) { if(str_starts_with($rule,'in:')) { $options = array_combine(explode(',',substr($rule,3)),explode(',',substr($rule,3))); } }
if(in_array($field,['currency','billing_currency'])) { $options=$currencies->pluck('name','code')->all(); }
if($field==='customer_id') { $options=$customers->pluck('name','id')->all(); }
if($field==='expense_category_id') { $options=$categories->pluck('name','id')->all(); }
if($field==='is_active') { $options=[1=>'Active',0=>'Inactive']; }
$isText = in_array($field,['description','notes','internal_notes','billing_address','address']);
@endphp
@if($field === 'featured_image_id')<x-media-picker :name="$field" :selected="$value"/>@continue @endif
<div class="field {{ $isText ? 'office-wide' : '' }}"><label for="{{ $field }}">{{ ucfirst(str_replace('_',' ',$field)) }} {{ str_contains($rules,'required') ? '*' : '(optional)' }}</label>
@if($options !== null)<select name="{{ $field }}" id="{{ $field }}" @required(str_contains($rules,'required'))><option value="">Choose an option</option>@foreach($options as $key=>$label)<option value="{{ $key }}" @selected((string)$value === (string)$key)>{{ ucfirst(str_replace('_',' ',$label)) }}</option>@endforeach</select>
@elseif($isText)<textarea name="{{ $field }}" id="{{ $field }}" rows="4" @required(str_contains($rules,'required'))>{{ $value }}</textarea>
@else<input name="{{ $field }}" id="{{ $field }}" type="{{ $field === 'password' ? 'password' : (str_contains($rules,'email') ? 'email' : (str_contains($rules,'|date') ? 'date' : (str_contains($rules,'numeric') || str_contains($rules,'integer') ? 'number' : 'text'))) }}" value="{{ $value }}" @if(str_contains($rules,'numeric')) step="0.001" min="0" @elseif(str_contains($rules,'integer')) step="1" min="0" @endif @required(str_contains($rules,'required'))>
@endif @error($field)<span class="field-error">{{ $message }}</span>@enderror</div>
@endforeach</div>
@if($module === 'customers')<p class="form-footnote">After saving, open the customer's wallet to record an opening balance or deposit. Wallets are kept separately for each currency.</p>@endif
@if($module === 'users' && $record)<p class="form-footnote">Leave the password empty to keep the existing password.</p>@endif
<div class="editor-actions"><button class="button">Save record &rarr;</button><a href="{{ route('admin.office.index', $module) }}">Cancel</a></div></form>
</x-admin-layout>