<?php

use App\Http\Controllers\Admin\AcademicYearController as AdminAcademicYearController;
use App\Http\Controllers\Admin\AuditLogController as AdminAuditLogController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\MessageReportController as AdminMessageReportController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Admin\SettingController as AdminSettingController;
use App\Http\Controllers\Admin\SubjectController as AdminSubjectController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\CourseJoinController;
use App\Http\Controllers\CourseMembershipController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DiscussionController;
use App\Http\Controllers\GradebookController;
use App\Http\Controllers\LearningMaterialController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuizAttemptController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\QuizQuestionController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SubmissionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : view('welcome');
})->name('home');

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'active'])
    ->name('dashboard');

/* ---------------------------------------------------------------------- */
/* Profil                                                                  */
/* ---------------------------------------------------------------------- */
Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/* ---------------------------------------------------------------------- */
/* Kelas                                                                   */
/* ---------------------------------------------------------------------- */
Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/kelas', [CourseController::class, 'index'])->name('courses.index');
    Route::get('/kelas/buat', [CourseController::class, 'create'])->name('courses.create');
    Route::post('/kelas', [CourseController::class, 'store'])
        ->middleware('throttle:content-write')
        ->name('courses.store');

    Route::get('/kelas/gabung', [CourseJoinController::class, 'create'])->name('courses.join');
    Route::post('/kelas/gabung', [CourseJoinController::class, 'store'])
        ->middleware('throttle:join-class')
        ->name('courses.join.store');

    Route::get('/undangan/{token}', [CourseJoinController::class, 'showByToken'])->name('courses.join.token');
    Route::post('/undangan/{token}', [CourseJoinController::class, 'storeByToken'])
        ->middleware('throttle:join-class')
        ->name('courses.join.token.store');

    Route::get('/kelas/{course}', [CourseController::class, 'show'])->name('courses.show');
    Route::get('/kelas/{course}/ubah', [CourseController::class, 'edit'])->name('courses.edit');
    Route::put('/kelas/{course}', [CourseController::class, 'update'])->name('courses.update');
    Route::delete('/kelas/{course}', [CourseController::class, 'destroy'])->name('courses.destroy');
    Route::post('/kelas/{course}/arsip', [CourseController::class, 'archive'])->name('courses.archive');
    Route::post('/kelas/{course}/kode-baru', [CourseController::class, 'regenerateCode'])->name('courses.code');
    Route::post('/kelas/{course}/keluar', [CourseMembershipController::class, 'leave'])->name('courses.leave');

    // Anggota
    Route::get('/kelas/{course}/anggota', [CourseMembershipController::class, 'index'])->name('course.members');
    Route::post('/kelas/{course}/anggota', [CourseMembershipController::class, 'store'])
        ->middleware('throttle:content-write')
        ->name('course.members.store');
    Route::delete('/kelas/{course}/anggota/{user}', [CourseMembershipController::class, 'destroy'])
        ->name('course.members.destroy');

    // Materi
    Route::get('/kelas/{course}/materi', [LearningMaterialController::class, 'index'])->name('materials.index');
    Route::get('/kelas/{course}/materi/buat', [LearningMaterialController::class, 'create'])->name('materials.create');
    Route::post('/kelas/{course}/materi', [LearningMaterialController::class, 'store'])
        ->middleware('throttle:content-write')
        ->name('materials.store');
    Route::get('/kelas/{course}/materi/{material}', [LearningMaterialController::class, 'show'])->name('materials.show');
    Route::get('/kelas/{course}/materi/{material}/ubah', [LearningMaterialController::class, 'edit'])->name('materials.edit');
    Route::put('/kelas/{course}/materi/{material}', [LearningMaterialController::class, 'update'])
        ->name('materials.update');
    Route::delete('/kelas/{course}/materi/{material}', [LearningMaterialController::class, 'destroy'])
        ->name('materials.destroy');
    Route::post('/kelas/{course}/materi/{material}/selesai', [LearningMaterialController::class, 'complete'])
        ->name('materials.complete');
    Route::post('/kelas/{course}/materi/{material}/batal-selesai', [LearningMaterialController::class, 'uncomplete'])
        ->name('materials.uncomplete');

    // Tugas & pengumpulan
    Route::get('/kelas/{course}/tugas', [AssignmentController::class, 'index'])->name('assignments.index');
    Route::get('/kelas/{course}/tugas/buat', [AssignmentController::class, 'create'])->name('assignments.create');
    Route::post('/kelas/{course}/tugas', [AssignmentController::class, 'store'])
        ->middleware('throttle:content-write')
        ->name('assignments.store');
    Route::get('/kelas/{course}/tugas/{assignment}/pengumpulan', [SubmissionController::class, 'index'])
        ->name('assignments.submissions');
    Route::post('/kelas/{course}/tugas/{assignment}/kumpul', [SubmissionController::class, 'store'])
        ->middleware('throttle:content-write')
        ->name('assignments.submit');
    Route::delete('/kelas/{course}/tugas/{assignment}/pengumpulan/{submission}', [SubmissionController::class, 'destroy'])
        ->name('assignments.submission.destroy');
    Route::post('/kelas/{course}/tugas/{assignment}/pengumpulan/{submission}/nilai', [SubmissionController::class, 'grade'])
        ->name('assignments.submission.grade');
    Route::get('/kelas/{course}/tugas/{assignment}', [AssignmentController::class, 'show'])->name('assignments.show');
    Route::get('/kelas/{course}/tugas/{assignment}/ubah', [AssignmentController::class, 'edit'])->name('assignments.edit');
    Route::put('/kelas/{course}/tugas/{assignment}', [AssignmentController::class, 'update'])->name('assignments.update');
    Route::delete('/kelas/{course}/tugas/{assignment}', [AssignmentController::class, 'destroy'])->name('assignments.destroy');

    // Kuis
    Route::get('/kelas/{course}/kuis', [QuizController::class, 'index'])->name('quizzes.index');
    Route::get('/kelas/{course}/kuis/buat', [QuizController::class, 'create'])->name('quizzes.create');
    Route::post('/kelas/{course}/kuis', [QuizController::class, 'store'])
        ->middleware('throttle:content-write')
        ->name('quizzes.store');
    Route::post('/kelas/{course}/kuis/{quiz}/soal', [QuizQuestionController::class, 'store'])
        ->name('quiz.questions.store');
    Route::put('/kelas/{course}/kuis/{quiz}/soal/{question}', [QuizQuestionController::class, 'update'])
        ->name('quiz.questions.update');
    Route::delete('/kelas/{course}/kuis/{quiz}/soal/{question}', [QuizQuestionController::class, 'destroy'])
        ->name('quiz.questions.destroy');
    Route::post('/kelas/{course}/kuis/{quiz}/urutan-soal', [QuizQuestionController::class, 'reorder'])
        ->name('quiz.questions.reorder');
    Route::post('/kelas/{course}/kuis/{quiz}/mulai', [QuizAttemptController::class, 'start'])
        ->name('quizzes.start');
    Route::get('/kelas/{course}/kuis/{quiz}/ambil/{attempt}', [QuizAttemptController::class, 'take'])
        ->name('quizzes.take');
    Route::post('/kelas/{course}/kuis/{quiz}/jawab/{attempt}', [QuizAttemptController::class, 'answer'])
        ->middleware('throttle:send-message')
        ->name('quizzes.answer');
    Route::post('/kelas/{course}/kuis/{quiz}/selesai/{attempt}', [QuizAttemptController::class, 'submit'])
        ->name('quizzes.submit');
    Route::get('/kelas/{course}/kuis/{quiz}/hasil/{attempt}', [QuizAttemptController::class, 'results'])
        ->name('quizzes.results');
    Route::post('/kelas/{course}/kuis/{quiz}/hasil/{attempt}/nilai', [QuizAttemptController::class, 'manualGrade'])
        ->name('quizzes.manualGrade');
    Route::get('/kelas/{course}/kuis/{quiz}', [QuizController::class, 'show'])->name('quizzes.show');
    Route::get('/kelas/{course}/kuis/{quiz}/ubah', [QuizController::class, 'edit'])->name('quizzes.edit');
    Route::put('/kelas/{course}/kuis/{quiz}', [QuizController::class, 'update'])->name('quizzes.update');
    Route::delete('/kelas/{course}/kuis/{quiz}', [QuizController::class, 'destroy'])->name('quizzes.destroy');

    // Nilai
    Route::get('/kelas/{course}/nilai', [GradebookController::class, 'index'])->name('grades.index');
    Route::post('/kelas/{course}/nilai/bobot', [GradebookController::class, 'updateWeights'])->name('grades.weights');
    Route::get('/kelas/{course}/nilai/ekspor', [GradebookController::class, 'export'])->name('grades.export');
    Route::get('/nilai-saya', [GradebookController::class, 'mine'])->name('grades.mine');

    // Pengumuman
    Route::get('/kelas/{course}/pengumuman', [AnnouncementController::class, 'index'])->name('announcements.index');
    Route::get('/kelas/{course}/pengumuman/buat', [AnnouncementController::class, 'create'])->name('announcements.create');
    Route::post('/kelas/{course}/pengumuman', [AnnouncementController::class, 'store'])
        ->middleware('throttle:content-write')
        ->name('announcements.store');
    Route::get('/kelas/{course}/pengumuman/{announcement}', [AnnouncementController::class, 'show'])->name('announcements.show');
    Route::get('/kelas/{course}/pengumuman/{announcement}/ubah', [AnnouncementController::class, 'edit'])->name('announcements.edit');
    Route::put('/kelas/{course}/pengumuman/{announcement}', [AnnouncementController::class, 'update'])->name('announcements.update');
    Route::delete('/kelas/{course}/pengumuman/{announcement}', [AnnouncementController::class, 'destroy'])->name('announcements.destroy');

    // Diskusi
    Route::get('/kelas/{course}/diskusi', [DiscussionController::class, 'index'])->name('discussions.index');
    Route::get('/kelas/{course}/diskusi/buat', [DiscussionController::class, 'create'])->name('discussions.create');
    Route::post('/kelas/{course}/diskusi', [DiscussionController::class, 'store'])
        ->middleware('throttle:content-write')
        ->name('discussions.store');
    Route::get('/kelas/{course}/diskusi/{thread}', [DiscussionController::class, 'show'])->name('discussions.show');
    Route::post('/kelas/{course}/diskusi/{thread}/balas', [DiscussionController::class, 'storePost'])
        ->middleware('throttle:content-write')
        ->name('discussions.reply');
    Route::post('/kelas/{course}/diskusi/{thread}/kunci', [DiscussionController::class, 'toggleLock'])->name('discussions.lock');
    Route::post('/kelas/{course}/diskusi/{thread}/sematkan', [DiscussionController::class, 'togglePin'])->name('discussions.pin');
    Route::delete('/kelas/{course}/diskusi/{thread}', [DiscussionController::class, 'destroyThread'])->name('discussions.destroy');
    Route::delete('/kelas/{course}/diskusi/{thread}/balasan/{post}', [DiscussionController::class, 'destroyPost'])->name('discussions.post.destroy');

    // Kalender
    Route::get('/kalender', [CalendarController::class, 'index'])->name('calendar.index');
    Route::get('/kalender/data', [CalendarController::class, 'data'])->name('calendar.data');
    Route::post('/kelas/{course}/kalender', [CalendarController::class, 'create'])
        ->name('calendar.store');
    Route::delete('/kalender/{event}', [CalendarController::class, 'destroy'])->name('calendar.destroy');

    // Percakapan
    Route::get('/percakapan', [ConversationController::class, 'index'])->name('conversations.index');
    Route::post('/percakapan', [ConversationController::class, 'store'])
        ->middleware('throttle:content-write')
        ->name('conversations.store');
    Route::get('/percakapan/{conversation}', [ConversationController::class, 'show'])->name('conversations.show');
    Route::put('/percakapan/{conversation}', [ConversationController::class, 'update'])->name('conversations.update');
    Route::delete('/percakapan/{conversation}', [ConversationController::class, 'destroy'])->name('conversations.destroy');
    Route::post('/percakapan/{conversation}/keluar', [ConversationController::class, 'leave'])->name('conversations.leave');
    Route::post('/percakapan/{conversation}/kirim', [MessageController::class, 'store'])
        ->middleware('throttle:send-message')
        ->name('messages.store');
    Route::get('/percakapan/{conversation}/pesan', [MessageController::class, 'history'])->name('messages.history');
    Route::get('/percakapan/{conversation}/pantau', [MessageController::class, 'poll'])->name('messages.poll');
    Route::post('/percakapan/{conversation}/baca', [MessageController::class, 'markRead'])->name('messages.read');
    Route::delete('/percakapan/{conversation}/pesan/{message}', [MessageController::class, 'destroy'])->name('messages.destroy');
    Route::post('/pengguna/{user}/blokir', [ConversationController::class, 'block'])->name('users.block');
    Route::post('/pengguna/{user}/batalkan-blokir', [ConversationController::class, 'unblock'])->name('users.unblock');
    Route::post('/pesan/{message}/lapor', [ConversationController::class, 'report'])
        ->middleware('throttle:content-write')
        ->name('messages.report');

    // Notifikasi
    Route::get('/notifikasi', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifikasi/ringkasan', [NotificationController::class, 'summary'])->name('notifications.summary');
    Route::post('/notifikasi/baca-semua', [NotificationController::class, 'readAll'])->name('notifications.readAll');
    Route::post('/notifikasi/{id}/baca', [NotificationController::class, 'read'])->name('notifications.read');

    // Pencarian
    Route::get('/cari', [SearchController::class, 'index'])->middleware('throttle:search')->name('search.index');

    // Lampiran (disk privat)
    Route::get('/lampiran/{attachment}', [AttachmentController::class, 'download'])
        ->name('attachments.download');
});

/* ---------------------------------------------------------------------- */
/* Panel admin                                                             */
/* ---------------------------------------------------------------------- */
Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'active', 'role:admin'])
    ->group(function () {
        Route::get('/', AdminDashboardController::class)->name('dashboard');

        Route::get('/pengguna', [AdminUserController::class, 'index'])->name('users.index');
        Route::get('/pengguna/buat', [AdminUserController::class, 'create'])->name('users.create');
        Route::post('/pengguna', [AdminUserController::class, 'store'])->name('users.store');
        Route::get('/pengguna/{user}/ubah', [AdminUserController::class, 'edit'])->name('users.edit');
        Route::put('/pengguna/{user}', [AdminUserController::class, 'update'])->name('users.update');
        Route::post('/pengguna/{user}/status', [AdminUserController::class, 'toggleStatus'])->name('users.status');
        Route::post('/pengguna/{user}/pulihkan', [AdminUserController::class, 'restore'])->name('users.restore');
        Route::delete('/pengguna/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');

        Route::get('/mata-pelajaran', [AdminSubjectController::class, 'index'])->name('subjects.index');
        Route::post('/mata-pelajaran', [AdminSubjectController::class, 'store'])->name('subjects.store');
        Route::put('/mata-pelajaran/{subject}', [AdminSubjectController::class, 'update'])->name('subjects.update');
        Route::delete('/mata-pelajaran/{subject}', [AdminSubjectController::class, 'destroy'])->name('subjects.destroy');

        Route::get('/tahun-ajaran', [AdminAcademicYearController::class, 'index'])->name('years.index');
        Route::post('/tahun-ajaran', [AdminAcademicYearController::class, 'store'])->name('years.store');
        Route::put('/tahun-ajaran/{academicYear}', [AdminAcademicYearController::class, 'update'])->name('years.update');
        Route::post('/tahun-ajaran/{academicYear}/aktifkan', [AdminAcademicYearController::class, 'activate'])->name('years.activate');
        Route::delete('/tahun-ajaran/{academicYear}', [AdminAcademicYearController::class, 'destroy'])->name('years.destroy');

        Route::get('/pengaturan', [AdminSettingController::class, 'edit'])->name('settings.edit');
        Route::put('/pengaturan', [AdminSettingController::class, 'update'])->name('settings.update');

        Route::get('/laporan', [AdminReportController::class, 'index'])->name('reports.index');
        Route::get('/laporan/ekspor', [AdminReportController::class, 'exportAll'])->name('reports.export');

        Route::get('/laporan-pesan', [AdminMessageReportController::class, 'index'])->name('message-reports.index');
        Route::post('/laporan-pesan/{report}', [AdminMessageReportController::class, 'resolve'])->name('message-reports.resolve');

        Route::get('/log-aktivitas', [AdminAuditLogController::class, 'index'])->name('audit-logs.index');
    });

require __DIR__.'/auth.php';
