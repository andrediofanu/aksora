<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CalendarEvent extends Model
{
    public const TYPE_AGENDA = 'agenda';
    public const TYPE_ASSIGNMENT = 'assignment';
    public const TYPE_QUIZ = 'quiz';
    public const TYPE_ANNOUNCEMENT = 'announcement';

    protected $fillable = [
        'course_id',
        'academic_year_id',
        'title',
        'description',
        'type',
        'reference_id',
        'starts_at',
        'ends_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function eventsForCourses($courseIds): HasMany
    {
        return $this->whereIn('course_id', $courseIds);
    }
}
