<?php

namespace App\Models;

use App\Support\Role;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
        'phone',
        'nis',
        'nip',
        'title',
        'avatar_path',
        'bio',
        'last_login_at',
        'email_verified_at',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_login_at' => 'datetime',
        ];
    }

    /* ------------------------------------------------------------------ */
    /* Relasi                                                             */
    /* ------------------------------------------------------------------ */

    /** Kelas yang diajar pengguna ini. */
    public function teachingCourses(): HasMany
    {
        return $this->hasMany(Course::class, 'teacher_id');
    }

    /** Keanggotaan kelas (baik guru maupun murid). */
    public function memberships(): HasMany
    {
        return $this->hasMany(CourseMembership::class);
    }

    /** Keanggotaan aktif. */
    public function activeMemberships(): HasMany
    {
        return $this->memberships()->where('status', 'active');
    }

    /** Kelas tempat pengguna menjadi murid (aktif). */
    public function enrolledCourses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'course_memberships')
            ->withPivot(['role', 'status', 'joined_at'])
            ->wherePivot('role', 'student')
            ->wherePivot('status', 'active')
            ->withTimestamps();
    }

    /** Kelas yang diajar (via keanggotaan role guru, aktif). */
    public function ledCourses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'course_memberships')
            ->withPivot(['role', 'status', 'joined_at'])
            ->wherePivot('role', 'teacher')
            ->wherePivot('status', 'active')
            ->withTimestamps();
    }

    public function conversations(): BelongsToMany
    {
        return $this->belongsToMany(Conversation::class, 'conversation_participants')
            ->withPivot(['last_read_at', 'role', 'is_muted', 'joined_at'])
            ->withTimestamps();
    }

    public function sentMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function blockedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'conversation_blocks', 'blocker_id', 'blocked_id')
            ->withTimestamps();
    }

    public function blockedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'conversation_blocks', 'blocked_id', 'blocker_id')
            ->withTimestamps();
    }

    /* ------------------------------------------------------------------ */
    /* Peran                                                              */
    /* ------------------------------------------------------------------ */

    public function isAdmin(): bool
    {
        return $this->role === Role::ADMIN;
    }

    public function isTeacher(): bool
    {
        return $this->role === Role::TEACHER;
    }

    public function isStudent(): bool
    {
        return $this->role === Role::STUDENT;
    }

    /** Admin dianggap memiliki akses baca ke seluruh konten akademik. */
    public function hasAcademicAccess(): bool
    {
        return $this->isAdmin() || $this->isTeacher();
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function roleLabel(): string
    {
        return Role::label($this->role);
    }

    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim($this->name), 3);
        $first = $parts[0] ?? '';
        $last = count($parts) > 1 ? end($parts) : '';

        return mb_strtoupper(mb_substr($first, 0, 1).($last !== '' ? mb_substr($last, 0, 1) : ''));
    }

    /**
     * Apakah pengguna berada pada kelas tertentu (aktif, dengan peran apa pun)?
     */
    public function isMemberOf(Course $course): bool
    {
        return $this->memberships()
            ->where('course_id', $course->id)
            ->where('status', 'active')
            ->exists();
    }

    public function teaches(Course $course): bool
    {
        return $this->memberships()
            ->where('course_id', $course->id)
            ->where('role', 'teacher')
            ->where('status', 'active')
            ->exists();
    }

    public function hasBlocked(User $user): bool
    {
        return $this->blockedUsers()->where('users.id', $user->id)->exists();
    }

    public function isBlockedBy(User $user): bool
    {
        return $this->blockedBy()->where('users.id', $user->id)->exists();
    }

    /** Total pesan belum dibaca di seluruh percakapan pengguna. */
    public function totalUnreadMessages(): int
    {
        return Message::query()
            ->join('conversation_participants as cp', function ($join) {
                $join->on('cp.conversation_id', '=', 'messages.conversation_id')
                    ->where('cp.user_id', '=', $this->id)
                    ->whereNull('cp.left_at');
            })
            ->where('messages.sender_id', '!=', $this->id)
            ->whereNull('messages.deleted_at')
            ->where(function ($query) {
                $query->whereNull('cp.last_read_at')
                    ->orWhereColumn('messages.created_at', '>', 'cp.last_read_at');
            })
            ->count('messages.id');
    }
}
