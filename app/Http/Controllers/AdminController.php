<?php

namespace App\Http\Controllers;

use App\Models\Enquiry;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function index(): View
    {
        $start = today()->subDays(29);
        $enquiries = Enquiry::query();
        $contactClicks = DB::table('website_contact_clicks')->where('day', '>=', $start->toDateString())
            ->selectRaw('action, SUM(clicks) as total')->groupBy('action')->pluck('total', 'action');

        return view('admin.dashboard', [
            'websiteMeasurement' => [
                'Successful website enquiries' => (clone $enquiries)->where('privacy_consent', true)->where('created_at', '>=', $start)->count(),
                'Qualified enquiries' => (clone $enquiries)->where('qualified_at', '>=', $start)->count(),
                'Consultations booked' => (clone $enquiries)->where('consultation_booked_at', '>=', $start)->count(),
                'Proposals sent' => (clone $enquiries)->where('proposal_sent_at', '>=', $start)->count(),
            ] + collect(ContactClickController::Actions)->mapWithKeys(fn (string $label, string $action): array => [$label => (int) $contactClicks->get($action, 0)])->all(),
            'customerCount' => DB::table('customers')->whereNull('deleted_at')->count(),
            'openInvoiceCount' => DB::table('invoices')->whereIn('status', ['issued', 'partially_paid'])->count(),
            'draftInvoiceCount' => DB::table('invoices')->where('status', 'draft')->count(),
            'newEnquiries' => Enquiry::where('status', 'new')->count(),
            'totalEnquiries' => Enquiry::count(),
            'enquiries' => Enquiry::latest()->orderByDesc('id')->limit(5)->get(),
        ]);
    }

    public function enquiries(): View
    {
        return view('admin.enquiries-index', [
            'enquiries' => Enquiry::latest()->orderByDesc('id')->paginate(15),
        ]);
    }

    public function showEnquiry(Enquiry $enquiry): View
    {
        return view('admin.enquiry-show', [
            'enquiry' => $enquiry,
            'enquiryOwners' => DB::table('users')->where('role', 'administrator')->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
