<?php

namespace App\Http\Controllers;

use App\Models\Enquiry;
use App\Support\LeadAttribution;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LeadSourceReportController extends Controller
{
    public function __invoke(Request $request): View|StreamedResponse
    {
        $filters = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', Rule::when($request->filled('from'), 'after_or_equal:from')],
            'source' => ['nullable', Rule::in([...array_keys(LeadAttribution::Sources), 'unattributed'])],
            'format' => ['nullable', 'in:csv'],
        ]);

        $query = Enquiry::query();
        if ($from = $filters['from'] ?? null) {
            $query->where('created_at', '>=', $from.' 00:00:00');
        }
        if ($to = $filters['to'] ?? null) {
            $query->where('created_at', '<', Carbon::parse($to)->addDay()->startOfDay());
        }
        if ($source = $filters['source'] ?? null) {
            if ($source === 'unattributed') {
                $query->whereNull('lead_source');
            } else {
                $query->where('lead_source', $source);
            }
        }

        $sources = LeadAttribution::Sources;
        $columns = ['id', 'created_at', 'lead_source', 'first_touch_attribution', 'last_touch_attribution'];
        if (($filters['format'] ?? null) === 'csv') {
            return response()->streamDownload(function () use ($query, $columns, $sources): void {
                $output = fopen('php://output', 'w');
                $utmHeaders = [];
                foreach (['first', 'last'] as $touch) {
                    foreach (LeadAttribution::UtmKeys as $key) {
                        $utmHeaders[] = $touch.'_'.$key;
                    }
                }
                fputcsv($output, ['Enquiry ID', 'Received at', 'First-touch source', 'Last-touch source', ...$utmHeaders], escape: '');

                foreach ($query->select($columns)->lazyById(500) as $enquiry) {
                    $first = $enquiry->first_touch_attribution ?? [];
                    $last = $enquiry->last_touch_attribution ?? [];
                    $row = [$enquiry->id, $enquiry->created_at?->toDateTimeString(), $sources[$first['source'] ?? ''] ?? 'Unattributed', $sources[$enquiry->lead_source] ?? 'Unattributed'];
                    foreach ([$first, $last] as $touch) {
                        foreach (LeadAttribution::UtmKeys as $key) {
                            $value = $touch[$key] ?? '';
                            $row[] = preg_match('/^[\s\p{C}]*[=+@-]/u', $value) ? "'".$value : $value;
                        }
                    }
                    fputcsv($output, $row, escape: '');
                }

                fclose($output);
            }, 'lead-sources.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
        }

        $totals = (clone $query)->selectRaw('lead_source, COUNT(*) AS total')->groupBy('lead_source')->pluck('total', 'lead_source');
        $metrics = [
            'Total enquiries' => $totals->sum(),
            'Google Business Profile enquiries' => $totals->get('gbp', 0),
            'Social media enquiries' => $totals->get('facebook', 0) + $totals->get('instagram', 0),
            'Organic search enquiries' => $totals->get('google_organic', 0),
        ];

        return view('admin.lead-sources', [
            'enquiries' => $query->latest()->orderByDesc('id')->paginate(15, $columns)->withQueryString(),
            'sources' => $sources,
            'filters' => $filters,
            'metrics' => $metrics,
        ]);
    }
}
