<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $previousDefaults = [
            'alfa-network' => [
                'category' => 'Mobile app / Professional networking',
                'description' => 'A member networking app for Achievers and Leaders Forum, bringing profiles, events and professional connections into one mobile experience.',
                'content' => "ALFA Network gives members of Achievers and Leaders Forum a focused space to discover people, build professional connections and stay informed about community activities. Its mobile experience brings member profiles, upcoming events and networking opportunities together.\n\nOur work on this project focuses on the member experience: making it easier to explore the community, follow events and access useful information from a mobile device. The accompanying website introduces the platform and provides Android and iOS download links.",
                'challenge' => 'Bring member discovery, event information and community engagement into an accessible experience for a professional network.',
                'solution' => 'A member-focused mobile platform supported by a clear app landing page, with access to profiles, events, opportunities and download links.',
                'outcome' => 'Members have a dedicated destination for staying connected with the forum and accessing its networking experience.',
            ],
            'e-school' => [
                'category' => 'Web application / School management',
                'description' => 'A cloud-based school management platform for student and staff records, examinations, payments, identity cards and school websites.',
                'content' => "E School brings everyday school administration into a cloud-based platform accessible from computers, laptops and smartphones. It supports student and staff profiles, identity cards, admission-related records, examination documents, results and school leaving certificates.\n\nOur work on E School brings these administrative tasks into a shared web application. The platform includes school and class management, fee payment records and receipts, staff salary records and payslips, and school website content, helping institutions manage information through a connected set of tools.",
                'challenge' => 'Organise school records, documents and payment information across several administrative responsibilities and institutions.',
                'solution' => 'A web platform with school, class, student and staff management alongside examinations, identity cards, payment records and school websites.',
                'outcome' => 'School teams can access their administrative tools and records through a central browser-based platform.',
            ],
        ];

        DB::transaction(function () use ($previousDefaults): void {
            foreach ($previousDefaults as $slug => $previous) {
                $project = DB::table('portfolio_projects')->where('slug', $slug)->whereNull('deleted_at')->where('is_demonstration', false)->lockForUpdate()->first();
                if (! $project) {
                    continue;
                }

                $updated = [];
                $replacement = config('featured-projects.'.$slug);
                foreach ($previous as $field => $value) {
                    if ($project->$field !== null && str_replace("\r\n", "\n", $project->$field) === $value && $value !== $replacement[$field]) {
                        $updated[$field] = $replacement[$field];
                    }
                }

                if ($updated !== []) {
                    DB::table('portfolio_projects')->where('id', $project->id)->update($updated + ['updated_at' => now()]);
                }
            }
        });
    }

    public function down(): void {}
};
