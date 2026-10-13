<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Course extends Model
{
    use SoftDeletes;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'name',
        'code',
        'invite_token',
        'description',
        'subject_id',
        'academic_year_id',
        'teacher_id',
        'grade_level',
        'room',
        'status',
        'max_members',
        'allow_student_private_message',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'allow_student_private_message' => 'boolean',
            'archived_at' => 'datetime',
        ];
    }

    /* ------------------------------------------------------------------ */
    /* Relasi                                                             */
    /* ------------------------------------------------------------------ */

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(CourseMembership::class);
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'course_memberships')
            ->withPivot(['status', 'joined_at'])
            ->wherePivot('role', 'student')
            ->wherePivot('status', 'active')
            ->withTimestamps();
    }

    public function materials(): HasMany
    {
        return $this->hasMany(LearningMaterial::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }

    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class);
    }

    public function announcements(): HasMany
    {
        return $this->hasMany(Announcement::class);
    }

    public function discussionThreads(): HasMany
    {
        return $this->hasMany(DiscussionThread::class);
    }

    public function gradeWeights(): HasMany
    {
        return $this->hasMany(GradeWeight::class);
    }

    public function calendarEvents(): HasMany
    {
        return $this->hasMany(CalendarEvent::class);
    }

    /* ------------------------------------------------------------------ */
    /* Aksesibilitas                                                      */
    /* ------------------------------------------------------------------ */

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function memberCount(): int
    {
        return $this->memberships()->where('status', 'active')->count();
    }

    public function studentCount(): int
    {
        return $this->memberships()
            ->where('role', 'student')
            ->where('status', 'active')
            ->count();
    }

    /** Tautan undangan publik (hanya dibuat saat dibutuhkan). */
    public function ensureInviteToken(): string
    {
        if (! $this->invite_token) {
            $this->forceFill(['invite_token' => Str::random(40)])->saveQuietly();
        }

        return $this->invite_token;
    }

    public function joinUrl(): string
    {
        return route('courses.join.token', $this->ensureInviteToken());
    }
}
