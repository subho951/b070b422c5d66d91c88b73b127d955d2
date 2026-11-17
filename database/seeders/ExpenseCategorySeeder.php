<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ExpenseCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Salaries & Employee Benefits' => 'Employee salaries, bonuses, allowances and employer benefit contributions.',
            'Freelancers & Contractors' => 'Payments to external developers, designers, consultants and other contractors.',
            'Software & SaaS Subscriptions' => 'Development tools, productivity apps, design software and business subscriptions.',
            'Cloud Infrastructure & Hosting' => 'Cloud servers, website hosting, databases, storage, CDN and infrastructure usage.',
            'Domains & SSL Certificates' => 'Domain registrations, domain renewals and paid SSL certificates.',
            'Hardware & IT Equipment' => 'Laptops, desktops, monitors, servers, peripherals and other equipment purchases.',
            'Equipment Repairs & Maintenance' => 'Repairs, servicing, replacement parts and maintenance agreements.',
            'Internet & Telecommunications' => 'Broadband, leased lines, business mobile plans, telephone and communication charges.',
            'Cybersecurity & Data Protection' => 'Security tools, antivirus, backups, security assessments and data protection services.',
            'Office Rent & Coworking' => 'Office rent, coworking memberships and meeting room rentals.',
            'Utilities & Office Operations' => 'Electricity, water, cleaning and day-to-day office operating costs.',
            'Office Supplies & Printing' => 'Stationery, printing, printer supplies and other office consumables.',
            'Marketing & Advertising' => 'Digital advertisements, SEO, promotional campaigns, branding and marketing services.',
            'Sales & Business Development' => 'Lead generation, business networking, proposal preparation and sales activities.',
            'Travel & Local Transportation' => 'Business travel, accommodation, taxis, mileage and local transportation.',
            'Training & Certifications' => 'Courses, technical certifications, books, workshops and professional development.',
            'Recruitment & Hiring' => 'Job advertisements, recruitment agencies, candidate assessments and hiring expenses.',
            'Legal, Accounting & Professional Fees' => 'Legal advice, accounting, bookkeeping, audits and other professional services.',
            'Bank & Payment Processing Fees' => 'Bank charges, payment gateway fees, transaction charges and transfer fees.',
            'Insurance' => 'Business, equipment, professional indemnity and cyber insurance premiums.',
            'Taxes, Licences & Registrations' => 'Business taxes, statutory filing charges, licences and registration renewals.',
            'Research & Development' => 'Prototypes, technical experiments, research tools and new product development costs.',
            'Client Meetings & Hospitality' => 'Business meals, refreshments and hospitality for client meetings.',
            'Miscellaneous Business Expenses' => 'Occasional business expenses that do not fit another category; include explanatory notes.',
        ];

        foreach ($categories as $name => $description) {
            DB::table('expense_categories')->insertOrIgnore([
                'name' => $name,
                'description' => $description,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
