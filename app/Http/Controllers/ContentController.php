<?php

namespace App\Http\Controllers;

use App\Models\Enquiry;
use App\Support\BrandingSettings;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ContentController extends Controller
{
    /** @return array{label: string, table: string, fields: array<int, string>} */
    private function definition(string $section): array
    {
        abort_unless(config('content.'.$section), 404);

        return config('content.'.$section);
    }

    public function index(string $section): View
    {
        $definition = $this->definition($section);
        $query = DB::table($definition['table'])->whereNull('deleted_at');
        if ($section === 'portfolio') {
            $query->orderByDesc('is_exclusive');
        }
        $records = $query->orderBy('sort_order')->orderByDesc('id')->paginate(15);

        return view('admin.content-index', compact('section', 'definition', 'records'));
    }

    public function create(string $section): View
    {
        $definition = $this->definition($section);
        abort_if($section === 'pages', 403);

        return view('admin.content-edit', ['section' => $section, 'definition' => $definition, 'record' => null]);
    }

    public function edit(string $section, int $id): View
    {
        $definition = $this->definition($section);
        $record = DB::table($definition['table'])->whereNull('deleted_at')->find($id);
        abort_unless($record, 404);

        return view('admin.content-edit', compact('section', 'definition', 'record'));
    }

    public function store(Request $request, string $section): RedirectResponse
    {
        $definition = $this->definition($section);
        abort_if($section === 'pages', 403);
        $data = $this->validated($request, $section);
        $data['created_at'] = now();
        DB::table($definition['table'])->insert($data);

        return to_route('admin.content.index', $section)->with('success', 'Content created.');
    }

    public function update(Request $request, string $section, int $id): RedirectResponse
    {
        $definition = $this->definition($section);
        abort_unless(DB::table($definition['table'])->whereNull('deleted_at')->find($id), 404);
        DB::table($definition['table'])->where('id', $id)->update($this->validated($request, $section, $id));

        return to_route('admin.content.index', $section)->with('success', 'Changes saved.');
    }

    public function homepage(Request $request, int $id): RedirectResponse
    {
        $record = DB::table('portfolio_projects')->whereNull('deleted_at')->find($id);
        abort_unless($record, 404);
        $request->validate(['show_on_homepage' => ['required', 'boolean']]);
        DB::table('portfolio_projects')->where('id', $id)->update(['show_on_homepage' => $request->boolean('show_on_homepage'), 'updated_at' => now()]);

        return back()->with('success', 'Homepage selection updated. Only published projects appear on the website.');
    }

    public function exclusive(Request $request, int $id): RedirectResponse
    {
        $record = DB::table('portfolio_projects')->whereNull('deleted_at')->find($id);
        abort_unless($record, 404);
        $request->validate(['exclusive' => ['required', 'boolean']]);
        $isExclusive = $request->boolean('exclusive');
        DB::table('portfolio_projects')->where('id', $id)->update(['is_exclusive' => $isExclusive, 'updated_at' => now()]);

        return back()->with('success', $isExclusive ? 'Project marked as exclusive.' : 'Project is no longer exclusive.');
    }

    public function visibility(Request $request, string $section, int $id): RedirectResponse
    {
        $definition = $this->definition($section);
        $record = DB::table($definition['table'])->whereNull('deleted_at')->find($id);
        abort_unless($record, 404);
        $request->validate(['visible' => ['required', 'boolean']]);
        $isVisible = $request->boolean('visible');
        if ($section === 'testimonials' && $isVisible && ! $record->consent_confirmed) {
            throw ValidationException::withMessages(['visibility' => 'Confirm client consent in the testimonial editor before making it visible.']);
        }
        DB::table($definition['table'])->where('id', $id)->update([
            'status' => $isVisible ? 'published' : 'draft',
            'published_at' => $isVisible ? now() : null,
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Visibility turned '.($isVisible ? 'on.' : 'off.'));
    }

    public function destroy(string $section, int $id): RedirectResponse
    {
        $definition = $this->definition($section);
        abort_if($section === 'pages', 403);
        $affected = DB::table($definition['table'])->whereNull('deleted_at')->where('id', $id)->update(['deleted_at' => now(), 'updated_at' => now()]);
        abort_unless($affected, 404);

        return back()->with('success', 'Content archived and removed from the website.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, string $section, ?int $id = null): array
    {
        $definition = $this->definition($section);
        $rules = ['title' => ['required', 'string', 'max:255'], 'description' => ['required', 'string', 'max:10000'], 'status' => ['required', Rule::in(['draft', 'published'])], 'sort_order' => ['required', 'integer', 'min:0', 'max:9999']];
        foreach ($definition['fields'] as $field) {
            if (! isset($rules[$field])) {
                $rules[$field] = ['nullable', 'string', 'max:50000'];
            }
        }
        if ($section === 'testimonials') {
            $rules['name'] = ['required', 'string', 'max:255'];
            $rules['company'] = ['nullable', 'string', 'max:255'];
            $rules['consent_confirmed'] = $request->input('status') === 'published' ? ['accepted'] : ['nullable', 'boolean'];
        } else {
            $rules['slug'] = $section === 'pages' ? ['prohibited'] : ['required', 'string', 'max:180', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique($definition['table'], 'slug')->ignore($id)];
        }
        if ($section === 'portfolio') {
            $rules['category'] = ['nullable', 'string', 'max:100'];
            $rules['is_demonstration'] = ['nullable', 'boolean'];
            $rules['is_exclusive'] = ['nullable', 'boolean'];
            $rules['show_on_homepage'] = ['nullable', 'boolean'];
            $rules['description'][0] = 'nullable';
            $rules['sort_order'][0] = 'nullable';
            $rules['slug'][0] = 'nullable';
        }
        if ($section === 'pages') {
            $rules['eyebrow'] = ['nullable', 'string', 'max:255'];
        }
        $rules['featured_image_id'] = ['nullable', 'integer', Rule::exists('media_assets', 'id')];
        $data = $request->validate($rules);
        if ($section === 'portfolio') {
            $existing = $id ? DB::table($definition['table'])->find($id) : null;
            $data['description'] = array_key_exists('description', $data) ? ($data['description'] ?? '') : ($existing->description ?? '');
            $data['sort_order'] = $data['sort_order'] ?? $existing->sort_order ?? 0;
            $data['is_demonstration'] = $data['is_demonstration'] ?? $existing->is_demonstration ?? false;
            $data['is_exclusive'] = $data['is_exclusive'] ?? $existing->is_exclusive ?? false;
            $data['show_on_homepage'] = $data['show_on_homepage'] ?? $existing->show_on_homepage ?? false;
            if (empty($data['slug'])) {
                $data['slug'] = $existing->slug ?? null;
                if (! $data['slug']) {
                    $baseSlug = Str::limit(Str::slug($data['title']) ?: 'project', 170, '');
                    $data['slug'] = $baseSlug;
                    $suffix = 2;
                    while (DB::table($definition['table'])->where('slug', $data['slug'])->exists()) {
                        $data['slug'] = $baseSlug.'-'.$suffix++;
                    }
                }
            }
        }
        if ($section === 'testimonials') {
            $data['consent_confirmed'] = $request->boolean('consent_confirmed');
        }
        $data['published_at'] = $data['status'] === 'published' ? now() : null;
        $data['updated_at'] = now();

        return $data;
    }

    public function enquiry(Request $request, Enquiry $enquiry): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in(['new', 'contacted', 'qualified', 'converted', 'closed'])]]);
        $enquiry->status = $data['status'];
        $enquiry->save();

        return back()->with('success', 'Enquiry status updated.');
    }

    public function saveSettings(Request $request): RedirectResponse
    {
        $rules = ['contact_email' => ['required', 'email', 'max:255'], 'contact_phone' => ['required', 'string', 'max:40'], 'contact_location' => ['required', 'string', 'max:255'], 'maintenance_enabled' => ['required', 'boolean'], 'maintenance_message' => ['required', 'string', 'max:1000']];
        $tab = $request->input('_tab');
        if (in_array($tab, ['general', 'maintenance'], true)) {
            $fields = $tab === 'general' ? ['contact_email', 'contact_phone', 'contact_location'] : ['maintenance_enabled', 'maintenance_message'];
            $rules = array_intersect_key($rules, array_flip($fields));
        }
        $data = $request->validate($rules);
        $branding = app(BrandingSettings::class);
        $brandingData = $tab === 'general' && $request->boolean('_include_branding') ? $branding->validated($request) : null;
        DB::transaction(function () use ($data, $branding, $brandingData): void {
            if ($brandingData !== null) {
                $branding->save($brandingData);
            }
            foreach ($data as $key => $value) {
                DB::table('site_settings')->updateOrInsert(['key' => $key], ['value' => (string) $value, 'updated_at' => now()]);
            }
        });

        return to_route('admin.settings', ['tab' => $tab === 'maintenance' ? 'maintenance' : 'general'])->with('success', 'Website settings saved.');
    }
}
