<?php

namespace App\Http\Controllers;

use App\Models\Enquiry;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function index(): View
    {
        return view('admin.dashboard', [
            'customerCount' => DB::table('customers')->whereNull('deleted_at')->count(),
            'openInvoiceCount' => DB::table('invoices')->whereIn('status', ['issued', 'partially_paid'])->count(),
            'draftInvoiceCount' => DB::table('invoices')->where('status', 'draft')->count(),
            'newEnquiries' => Enquiry::where('status', 'new')->count(),
            'totalEnquiries' => Enquiry::count(),
            'enquiries' => Enquiry::latest()->paginate(15),
        ]);
    }
}
