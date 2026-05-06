<?php

namespace App\Models;

use App\Enums\StudentParentRelation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentParent extends Model
{
    protected $fillable = [
        'student_profile_id',
        'relation',
        'full_name',
        'occupation',
        'monthly_income_band',
        'nik',
        'birth_date',
    ];

    protected function casts(): array
    {
        return [
            'relation' => StudentParentRelation::class,
            'birth_date' => 'date',
        ];
    }

    public function studentProfile(): BelongsTo
    {
        return $this->belongsTo(StudentProfile::class);
    }
}
