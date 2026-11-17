<?php

namespace App\Http\Controllers;

use App\Support\BrandingSettings;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class WebsiteDesignController extends Controller
{
    public function index(): View
    {
        return view('admin.sections', ['sections' => DB::table('website_sections')->orderBy('scope')->orderBy('sort_order')->get()]);
    }

    public function edit(int $id): View
    {
        $record = DB::table('website_sections')->find($id);
        abort_unless($record, 404);
        $definition = config('website.sections')[$record->key] ?? null;
        abort_unless($definition, 404);

        return view('admin.section-edit', ['record' => $record, 'definition' => $definition, 'data' => json_decode($record->data, true)]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $record = DB::table('website_sections')->find($id);
        abort_unless($record, 404);
        $definition = config('website.sections')[$record->key] ?? null;
        abort_unless($definition, 404);
        $rules = ['is_visible' => ['required', 'boolean'], 'sort_order' => ['required', 'integer', 'min:0', 'max:9999'], 'data' => ['required', 'array:'.implode(',', array_keys($definition['fields']))]];
        foreach ($definition['fields'] as $key => $field) {
            $name = 'data.'.$key;
            $rules[$name] = match ($field['type']) {
                'image' => ['nullable', 'integer', Rule::exists('media_assets', 'id')],
                'items' => ['nullable', 'array', 'max:40'],
                'link' => app(BrandingSettings::class)->linkRules(),
                default => ['nullable', 'string', 'max:10000'],
            };
            if ($field['type'] === 'items') {
                $rules[$name.'.*'] = ['array:title,text,url'];
                $rules[$name.'.*.title'] = ['required', 'string', 'max:255'];
                $rules[$name.'.*.text'] = ['nullable', 'string', 'max:10000'];
                $rules[$name.'.*.url'] = app(BrandingSettings::class)->linkRules();
            }
        }
        $validated = $request->validate($rules);
        $data = $validated['data'];
        foreach ($definition['fields'] as $key => $field) {
            $data[$key] = $field['type'] === 'items' ? array_values($data[$key] ?? []) : ($data[$key] ?? null);
        }
        DB::table('website_sections')->where('id', $id)->update(['data' => json_encode($data, JSON_THROW_ON_ERROR), 'is_visible' => $validated['is_visible'], 'sort_order' => $validated['sort_order'], 'updated_at' => now()]);

        return to_route('admin.sections.index')->with('success', 'Website section updated.');
    }

    public function visibility(Request $request, int $id): RedirectResponse
    {
        $record = DB::table('website_sections')->find($id);
        abort_unless($record, 404);
        $definition = config('website.sections')[$record->key] ?? null;
        abort_unless($definition, 404);
        abort_if(in_array($definition['variant'], ['labels', 'screen'], true), 422, 'This section is always available.');
        $request->validate(['visible' => ['required', 'boolean']]);
        DB::table('website_sections')->where('id', $id)->update(['is_visible' => $request->boolean('visible'), 'updated_at' => now()]);

        return back()->with('success', 'Section visibility updated.');
    }

    public function appearance(): RedirectResponse
    {
        return to_route('admin.settings', ['tab' => 'general']);
    }

    public function saveAppearance(Request $request): RedirectResponse
    {
        $branding = app(BrandingSettings::class);
        $branding->save($branding->validated($request));

        return to_route('admin.settings', ['tab' => 'general'])->with('success', 'Branding and shared website copy saved.');
    }
}
