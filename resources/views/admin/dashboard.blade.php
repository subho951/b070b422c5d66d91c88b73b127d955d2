<x-admin-layout title="Dashboard">
<section class="dashboard-workspace">
<div class="section-heading dashboard-heading"><div><p class="eyebrow">YOUR WORKSPACE AT A GLANCE</p><h1>Dashboard</h1><p>A clear view of your customers, billing and enquiries.</p></div><div class="dashboard-actions"><a class="button button-secondary" href="{{ route('admin.office.create','customers') }}"><x-admin-icon name="users"/>Add customer</a><a class="button" href="{{ route('admin.invoices.create') }}"><x-admin-icon name="plus"/>Create invoice</a></div></div>
<div class="metric-grid dashboard-metrics">
<article class="metric-card metric-blue"><div class="metric-top"><span>Customers</span><span class="metric-icon"><x-admin-icon name="users"/></span></div><strong>{{ $customerCount }}</strong><a href="{{ route('admin.office.index','customers') }}">Customers & wallets <x-admin-icon name="arrow"/></a></article>
<article class="metric-card metric-violet"><div class="metric-top"><span>Awaiting payment</span><span class="metric-icon"><x-admin-icon name="file"/></span></div><strong>{{ $openInvoiceCount }}</strong><a href="{{ route('admin.invoices.index') }}">Manage invoices <x-admin-icon name="arrow"/></a></article>
<article class="metric-card metric-teal"><div class="metric-top"><span>New enquiries</span><span class="metric-icon"><x-admin-icon name="mail"/></span></div><strong>{{ $newEnquiries }}</strong><a href="{{ route('admin.enquiries.index') }}">{{ $totalEnquiries }} enquiries received in total <x-admin-icon name="arrow"/></a></article>
<article class="metric-card metric-amber"><div class="metric-top"><span>Draft invoices</span><span class="metric-icon"><x-admin-icon name="briefcase"/></span></div><strong>{{ $draftInvoiceCount }}</strong><a href="{{ route('admin.invoices.index',['status'=>'draft']) }}">Review drafts <x-admin-icon name="arrow"/></a></article>
</div>
<div class="dashboard-shortcuts"><a href="{{ route('admin.office.index','expenses') }}"><span class="shortcut-icon"><x-admin-icon name="wallet"/></span><div><strong>Expense management</strong><span>Keep business spending organised</span></div><x-admin-icon name="arrow"/></a><a href="{{ route('admin.reports.show','sales') }}"><span class="shortcut-icon"><x-admin-icon name="chart"/></span><div><strong>Business reports</strong><span>Review sales and expenses</span></div><x-admin-icon name="arrow"/></a><a href="{{ route('admin.content.index','pages') }}"><span class="shortcut-icon"><x-admin-icon name="file"/></span><div><strong>Website content</strong><span>Keep your pages up to date</span></div><x-admin-icon name="arrow"/></a></div>
<div class="table-panel">
    <div class="table-heading"><h2>Website measurement</h2><span>Last 30 days</span></div>
    <div class="table-scroll"><table><tbody>
        @foreach($websiteMeasurement as $label => $count)
            <tr><th scope="row">{{ $label }}</th><td>{{ number_format($count) }}</td></tr>
        @endforeach
    </tbody></table></div>
</div>
<section class="table-panel" aria-labelledby="recent-enquiries-heading">
    <div class="table-heading"><h2 id="recent-enquiries-heading">Recent enquiries</h2><a href="{{ route('admin.enquiries.index') }}">View all enquiries</a></div>
    @include('admin.enquiries-table')
</section>
</section></x-admin-layout>
