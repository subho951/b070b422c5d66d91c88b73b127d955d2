<?php

namespace Database\Factories;

use App\Models\Enquiry;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Enquiry> */
class EnquiryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(), 'email' => fake()->safeEmail(), 'company' => fake()->company(), 'country' => fake()->country(),
            'project_type' => 'custom-software', 'description' => 'Demonstration enquiry for automated testing of a software project.', 'privacy_consent' => true,
        ];
    }
}
