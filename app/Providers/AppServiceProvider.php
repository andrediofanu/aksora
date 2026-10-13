<?php

namespace App\Providers;

use App\Models\AcademicYear;
use App\Models\Announcement;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Course;
use App\Models\DiscussionPost;
use App\Models\DiscussionThread;
use App\Models\LearningMaterial;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Subject;
use App\Models\User;
use App\Policies\AcademicYearPolicy;
use App\Policies\AnnouncementPolicy;
use App\Policies\AssignmentPolicy;
use App\Policies\AssignmentSubmissionPolicy;
use App\Policies\CoursePolicy;
use App\Policies\DiscussionPolicy;
use App\Policies\LearningMaterialPolicy;
use App\Policies\QuizAttemptPolicy;
use App\Policies\QuizPolicy;
use App\Policies\SubjectPolicy;
use App\Policies\UserPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    protected $policies = [
        Course::class => CoursePolicy::class,
        LearningMaterial::class => LearningMaterialPolicy::class,
        Assignment::class => AssignmentPolicy::class,
        AssignmentSubmission::class => AssignmentSubmissionPolicy::class,
        Quiz::class => QuizPolicy::class,
        QuizAttempt::class => QuizAttemptPolicy::class,
        Announcement::class => AnnouncementPolicy::class,
        DiscussionThread::class => DiscussionPolicy::class,
        DiscussionPost::class => DiscussionPolicy::class,
        User::class => UserPolicy::class,
        Subject::class => SubjectPolicy::class,
        AcademicYear::class => AcademicYearPolicy::class,
    ];

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        foreach ($this->policies as $model => $policy) {
            Gate::policy($model, $policy);
        }

        Gate::define('access-admin-panel', fn (User $user) => $user->isAdmin());
        Gate::define('manage-platform', fn (User $user) => $user->isAdmin());

        $this->registerRateLimiters();

        if (app()->environment('production')) {
            URL::forceScheme('https');
        }
    }

    private function registerRateLimiters(): void
    {
        // Batas pengiriman pesan: mencegah spam/abuse.
        RateLimiter::for('send-message', function (Request $request) {
            $user = $request->user();

            return Limit::perMinute(60)->by($user ? 'message-'.$user->id : $request->ip());
        });

        // Batas umum untuk aksi pembuatan konten (form post).
        RateLimiter::for('content-write', function (Request $request) {
            $user = $request->user();

            return Limit::perMinute(30)->by($user ? 'write-'.$user->id : $request->ip());
        });

        // Pencarian global.
        RateLimiter::for('search', function (Request $request) {
            $user = $request->user();

            return Limit::perMinute(60)->by($user ? 'search-'.$user->id : $request->ip());
        });

        // Bergabung ke kelas (cegah brute-force kode kelas).
        RateLimiter::for('join-class', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });
    }
}
