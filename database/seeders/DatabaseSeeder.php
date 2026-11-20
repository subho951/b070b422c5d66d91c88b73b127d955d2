<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([WebsiteSeeder::class, AdminSeeder::class, ExpenseCategorySeeder::class, ServiceContentSeeder::class, CompanyPageContentSeeder::class, PortfolioContentSeeder::class, ClientTestimonialsSeeder::class]);
    }
}
