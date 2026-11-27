<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Enquiry extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'email', 'company', 'country', 'phone', 'intent', 'project_type', 'budget', 'budget_currency', 'timeline', 'description', 'privacy_consent', 'first_touch_attribution', 'last_touch_attribution', 'lead_source'];

    protected function casts(): array
    {
        return [
            'privacy_consent' => 'boolean',
            'first_touch_attribution' => 'array',
            'last_touch_attribution' => 'array',
        ];
    }
}
