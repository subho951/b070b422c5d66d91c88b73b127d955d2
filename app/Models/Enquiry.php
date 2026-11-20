<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Enquiry extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'email', 'company', 'country', 'phone', 'intent', 'project_type', 'budget', 'budget_currency', 'timeline', 'description', 'privacy_consent'];

    protected function casts(): array
    {
        return ['privacy_consent' => 'boolean'];
    }
}
