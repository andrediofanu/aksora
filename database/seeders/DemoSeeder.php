<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Announcement;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\CalendarEvent;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Course;
use App\Models\CourseMembership;
use App\Models\DiscussionPost;
use App\Models\DiscussionThread;
use App\Models\GradeWeight;
use App\Models\LearningMaterial;
use App\Models\Message;
use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use App\Models\Setting;
use App\Models\Subject;
use App\Models\User;
use App\Services\ConversationService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Seeder demo berisi data FIKTIF. Tidak ada kredensial produksi.
 *
 * Akun demo (semua password: "password"):
 *   admin@lms.test  – Admin
 *   guru@lms.test   – Guru (Matematika)
 *   guru2@lms.test  – Guru (Informatika)
 *   murid1..4@lms.test – Murid
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('password');

        DB::transaction(function () use ($password) {
            Setting::put('school_name', 'SMA Diofanu', 'general');
            Setting::put('app_description', 'Sistem Manajemen Pembelajaran', 'general');
            Setting::put('allow_student_private_message', '1', 'general');
            Setting::put('allow_student_join_by_code', '1', 'general');
            Setting::put('max_upload_mb', '10', 'general');
            Setting::put('grade_rounding', '2', 'general');
            Setting::put('registration_open', '1', 'general');

            /* ---------------------------------------------------------- */
            /* Pengguna                                                    */
            /* ---------------------------------------------------------- */
            $admin = User::firstOrCreate(['email' => 'admin@lms.test'], [
                'name' => 'Administrator Sekolah',
                'password' => $password,
                'role' => 'admin',
                'status' => 'active',
                'email_verified_at' => now(),
            ]);

            $teacher1 = User::firstOrCreate(['email' => 'guru@lms.test'], [
                'name' => 'Budi Santoso',
                'password' => $password,
                'role' => 'teacher',
                'status' => 'active',
                'nip' => '198701122015031001',
                'title' => 'S.Pd., M.Pd.',
                'email_verified_at' => now(),
            ]);

            $teacher2 = User::firstOrCreate(['email' => 'guru2@lms.test'], [
                'name' => 'Siti Aminah',
                'password' => $password,
                'role' => 'teacher',
                'status' => 'active',
                'nip' => '199004252019032002',
                'title' => 'S.Kom., M.T.',
                'email_verified_at' => now(),
            ]);

            $students = collect([
                ['murid1@lms.test', 'Ahmad Fauzi'],
                ['murid2@lms.test', 'Dewi Lestari'],
                ['murid3@lms.test', 'Rizky Pratama'],
                ['murid4@lms.test', 'Nabila Zahra'],
            ])->map(fn (array $s) => User::firstOrCreate(['email' => $s[0]], [
                'name' => $s[1],
                'password' => $password,
                'role' => 'student',
                'status' => 'active',
                'nis' => '2026'.str_pad((string) (random_int(1, 9999)), 4, '0', STR_PAD_LEFT),
                'email_verified_at' => now(),
            ]));

            /* ---------------------------------------------------------- */
            /* Master                                                      */
            /* ---------------------------------------------------------- */
            $year = AcademicYear::firstOrCreate(
                ['name' => '2026/2027'],
                [
                    'starts_on' => '2026-07-01',
                    'ends_on' => '2027-06-30',
                    'is_active' => true,
                    'description' => 'Tahun ajaran ganjil 2026/2027',
                ],
            );

            AcademicYear::where('id', '!=', $year->id)->update(['is_active' => false]);

            $math = Subject::firstOrCreate(['code' => 'MTK'], [
                'name' => 'Matematika',
                'description' => 'Mata pelajaran Matematika',
                'is_active' => true,
            ]);

            $informatics = Subject::firstOrCreate(['code' => 'INF'], [
                'name' => 'Informatika',
                'description' => 'Mata pelajaran Informatika',
                'is_active' => true,
            ]);

            /* ---------------------------------------------------------- */
            /* Kelas                                                       */
            /* ---------------------------------------------------------- */
            $course1 = Course::firstOrCreate(['name' => 'Matematika Kelas X IPA 1'], [
                'code' => 'MTKX1A',
                'description' => 'Kelas Matematika untuk jenjang kelas X IPA semester ganjil.',
                'subject_id' => $math->id,
                'academic_year_id' => $year->id,
                'teacher_id' => $teacher1->id,
                'grade_level' => 'Kelas X',
                'room' => 'R-101',
                'status' => 'active',
                'max_members' => 40,
                'allow_student_private_message' => true,
            ]);

            $course2 = Course::firstOrCreate(['name' => 'Informatika Kelas XI'], [
                'code' => 'INF11B',
                'description' => 'Kelas Informatika untuk jenjang kelas XI.',
                'subject_id' => $informatics->id,
                'academic_year_id' => $year->id,
                'teacher_id' => $teacher2->id,
                'grade_level' => 'Kelas XI',
                'room' => 'Lab 2',
                'status' => 'active',
                'max_members' => 40,
                'allow_student_private_message' => true,
            ]);

            foreach ([[$course1, $teacher1], [$course2, $teacher2]] as [$course, $owner]) {
                CourseMembership::updateOrCreate(
                    ['course_id' => $course->id, 'user_id' => $owner->id],
                    ['role' => 'teacher', 'status' => 'active', 'joined_source' => 'teacher', 'joined_at' => now()],
                );

                foreach (\App\Services\GradeCalculator::DEFAULT_WEIGHTS as $category => $weight) {
                    GradeWeight::updateOrCreate(
                        ['course_id' => $course->id, 'category' => $category],
                        ['weight' => $weight],
                    );
                }
            }

            // Keanggotaan murid.
            $memberships = [
                $course1->id => [$students[0], $students[1], $students[2]],
                $course2->id => [$students[1], $students[3]],
            ];

            foreach ($memberships as $courseId => $list) {
                foreach ($list as $student) {
                    CourseMembership::updateOrCreate(
                        ['course_id' => $courseId, 'user_id' => $student->id],
                        ['role' => 'student', 'status' => 'active', 'joined_source' => 'code', 'joined_at' => now()],
                    );
                }
            }

            $conversationService = app(ConversationService::class);

            foreach ([$course1, $course2] as $course) {
                $classConversation = $conversationService->ensureClassConversation($course);
                $conversationService->syncClassParticipants($classConversation, $course);
            }

            /* ---------------------------------------------------------- */
            /* Materi                                                      */
            /* ---------------------------------------------------------- */
            $materials = [
                [$course1, 'Pengenalan Bilangan Real', 'Pertemuan 1', 'published',
                    'Bilangan real mencakup bilangan rasional dan irasional. Pelajari sifat dan penjumlahannya.'],
                [$course1, 'Fungsi Kuadrat', 'Pertemuan 2', 'published',
                    'Bentuk umum fungsi kuadrat, akar-akar, dan grafik parabola.'],
                [$course1, 'Trigonometri Dasar', 'Pertemuan 3', 'published',
                    'Sinus, kosinus, dan tangen pada segitiga siku-siku.'],
                [$course1, 'Latihan Penyelesaian Persamaan', 'Pertemuan 4', 'draft',
                    'Materi ini masih disiapkan dan belum diterbitkan.'],
                [$course2, 'Konsep Algoritma', 'Pertemuan 1', 'published',
                    'Pengertian algoritma, pseudocode, dan diagram alir.'],
                [$course2, 'Struktur Data Dasar', 'Pertemuan 2', 'published',
                    'Array, stack, queue, dan pohon biner.'],
            ];

            foreach ($materials as $index => [$course, $title, $topic, $status, $content]) {
                LearningMaterial::firstOrCreate(
                    ['course_id' => $course->id, 'title' => $title],
                    [
                        'topic' => $topic,
                        'description' => fake()->sentence(12),
                        'content' => '<p>'.$content.'</p><p>'.fake()->paragraph().'</p>',
                        'sort_order' => $index + 1,
                        'status' => $status,
                        'published_at' => $status === 'published' ? now()->subDays(5) : null,
                        'created_by' => $course->teacher_id,
                    ],
                );
            }

            /* ---------------------------------------------------------- */
            /* Tugas + pengumpulan + nilai                                 */
            /* ---------------------------------------------------------- */
            $assignments = [
                [$course1, 'Tugas Bab 1 — Bilangan Real', now()->addDays(5), 'text', 100],
                [$course1, 'Tugas Bab 2 — Fungsi Kuadrat', now()->subDays(2), 'both', 100],
                [$course2, 'Tugas 1 — Diagram Alir', now()->addDays(7), 'file', 100],
                [$course2, 'Tugas 2 — Pseudocode', now()->addDays(-1), 'text', 80],
            ];

            $scoredSubmissions = 0;

            foreach ($assignments as [$course, $title, $due, $type, $max]) {
                $assignment = Assignment::firstOrCreate(
                    ['course_id' => $course->id, 'title' => $title],
                    [
                        'instructions' => 'Kerjakan seluruh soal dengan teliti. Kumpulkan tepat waktu.',
                        'submission_type' => $type,
                        'due_at' => $due,
                        'allow_late' => false,
                        'max_score' => $max,
                        'weight' => 1,
                        'status' => 'published',
                        'published_at' => now()->subDays(6),
                        'created_by' => $course->teacher_id,
                    ],
                );

                $memberIds = CourseMembership::where('course_id', $course->id)
                    ->where('role', 'student')
                    ->where('status', 'active')
                    ->pluck('user_id');

                foreach ($memberIds as $index => $studentId) {
                    // Hanya separuh murid mengumpulkan agar terlihat status "belum".
                    if ($index % 2 === 1 && $due->isFuture()) {
                        continue;
                    }

                    $graded = $due->isPast() && $index < $memberIds->count();

                    AssignmentSubmission::updateOrCreate(
                        ['assignment_id' => $assignment->id, 'user_id' => $studentId],
                        [
                            'content' => 'Berikut jawaban saya: '.$assignment->title,
                            'submitted_at' => $due->copy()->subDays(1),
                            'is_late' => false,
                            'status' => $graded ? 'graded' : 'submitted',
                            'score' => $graded ? min($max, 70 + ($scoredSubmissions++ % 4) * 7) : null,
                            'feedback' => $graded ? 'Jawaban sudah baik, pertahankan.' : null,
                            'graded_at' => $graded ? now()->subDay() : null,
                            'graded_by' => $graded ? $course->teacher_id : null,
                        ],
                    );
                }
            }

            /* ---------------------------------------------------------- */
            /* Kuis + percobaan                                             */
            /* ---------------------------------------------------------- */
            $quiz1 = Quiz::firstOrCreate(
                ['course_id' => $course1->id, 'title' => 'Kuis Bab 1 — Bilangan Real'],
                [
                    'description' => 'Uji pemahaman konsep bilangan real.',
                    'time_limit_minutes' => 20,
                    'opens_at' => now()->subDays(2),
                    'closes_at' => now()->addDays(6),
                    'max_attempts' => 2,
                    'show_results' => 'immediately',
                    'pass_score' => 70,
                    'status' => 'published',
                    'published_at' => now()->subDays(3),
                    'created_by' => $teacher1->id,
                ],
            );

            $questions = [
                ['Bilangan apa yang termasuk irasional?', ['√2', '0,5', '-3', '7/8'], 0, 5],
                ['Hasil dari 3² + 4² adalah…', ['7', '12', '25', '14'], 2, 5],
                ['Tentukan akar dari persamaan x² − 5x + 6 = 0.', null, null, 10],
            ];

            foreach ($questions as $index => [$text, $options, $correct, $points]) {
                $question = $quiz1->questions()->firstOrCreate(
                    ['question' => $text],
                    [
                        'type' => $options ? 'multiple_choice' : 'short_answer',
                        'points' => $points,
                        'sort_order' => $index + 1,
                        'correct_answer' => $options ? null : '2 dan 3',
                    ],
                );

                if ($options) {
                    foreach ($options as $i => $optionText) {
                        $question->options()->firstOrCreate(
                            ['option_text' => $optionText],
                            ['is_correct' => $i === $correct, 'sort_order' => $i + 1],
                        );
                    }
                }
            }

            $course1Students = CourseMembership::where('course_id', $course1->id)
                ->where('role', 'student')->where('status', 'active')->pluck('user_id');

            foreach ($course1Students as $i => $studentId) {
                $attempt = QuizAttempt::firstOrCreate(
                    ['quiz_id' => $quiz1->id, 'user_id' => $studentId, 'attempt_no' => 1],
                    [
                        'started_at' => now()->subDay(),
                        'submitted_at' => now()->subDay(),
                        'score' => [15, 10, 5, 15][$i % 4],
                        'status' => 'graded',
                        'needs_manual_review' => false,
                    ],
                );
            }

            /* ---------------------------------------------------------- */
            /* Pengumuman, diskusi, kalender                               */
            /* ---------------------------------------------------------- */
            foreach ([$course1, $course2] as $course) {
                Announcement::firstOrCreate(
                    ['course_id' => $course->id, 'title' => 'Selamat datang di kelas '.$course->name],
                    [
                        'content' => 'Selamat mengikuti pembelajaran semester ini. Silakan periksa materi dan tugas mingguan.',
                        'is_pinned' => true,
                        'published_at' => now()->subDays(4),
                        'created_by' => $course->teacher_id,
                    ],
                );

                Announcement::firstOrCreate(
                    ['course_id' => $course->id, 'title' => 'Jadwal konsultasi mingguan'],
                    [
                        'content' => 'Konsultasi dibuka setiap Jumat pukul 14.00–16.00 di ruang guru.',
                        'is_pinned' => false,
                        'published_at' => now()->subDays(1),
                        'created_by' => $course->teacher_id,
                    ],
                );

                $thread = DiscussionThread::firstOrCreate(
                    ['course_id' => $course->id, 'title' => 'Tanya jawab materi awal'],
                    [
                        'user_id' => $course->teacher_id,
                        'last_post_at' => now()->subDays(2),
                    ],
                );

                DiscussionPost::firstOrCreate(
                    ['thread_id' => $thread->id, 'user_id' => $course->teacher_id, 'parent_id' => null],
                    ['body' => 'Silakan ajukan pertanyaan terkait materi pertama di sini.'],
                );

                $firstStudent = $memberships[$course->id][0] ?? null;

                if ($firstStudent) {
                    DiscussionPost::firstOrCreate(
                        ['thread_id' => $thread->id, 'user_id' => $firstStudent->id, 'parent_id' => null],
                        ['body' => 'Apakah boleh contoh soal tambahan untuk materi ini?'],
                    );
                }

                CalendarEvent::firstOrCreate(
                    ['course_id' => $course->id, 'title' => 'Konsultasi bebas '.$course->name],
                    [
                        'academic_year_id' => $course->academic_year_id,
                        'description' => 'Sesi tanya jawab terbuka.',
                        'type' => 'agenda',
                        'starts_at' => now()->addDays(3)->setTime(14, 0),
                        'ends_at' => now()->addDays(3)->setTime(16, 0),
                        'created_by' => $course->teacher_id,
                    ],
                );
            }

            /* ---------------------------------------------------------- */
            /* Percakapan contoh                                            */
            /* ---------------------------------------------------------- */
            $direct = $conversationService->findOrCreateDirect($teacher1, $students[0]);

            $existingDirectMessage = Message::where('conversation_id', $direct->id)->exists();

            if (! $existingDirectMessage) {
                foreach ([
                    [$teacher1->id, 'Halo Ahmad, tolong periksa tugas Bab 2 ya.'],
                    [$students[0]->id, 'Baik pak, siap. Saya sudah mengumpulkan semalam.'],
                    [$teacher1->id, 'Terima kasih, nanti saya beri nilai hari ini.'],
                ] as [$senderId, $body]) {
                    Message::create([
                        'conversation_id' => $direct->id,
                        'sender_id' => $senderId,
                        'body' => $body,
                        'type' => 'text',
                    ]);
                }

                $direct->update(['last_message_at' => now()]);
            }

            $group = Conversation::firstOrCreate(
                ['type' => 'group', 'title' => 'Panitia Bulan Bahasa'],
                ['created_by' => $teacher1->id],
            );

            foreach ([$teacher1->id, $teacher2->id, $students[0]->id, $students[3]->id] as $index => $userId) {
                ConversationParticipant::firstOrCreate(
                    ['conversation_id' => $group->id, 'user_id' => $userId],
                    ['role' => $index === 0 ? 'owner' : 'member', 'joined_at' => now()],
                );
            }

            if (! Message::where('conversation_id', $group->id)->exists()) {
                Message::create([
                    'conversation_id' => $group->id,
                    'sender_id' => $teacher1->id,
                    'body' => 'Mari rapat persiapan Bulan Bahasa hari Sabtu pukul 09.00.',
                    'type' => 'text',
                ]);

                $group->update(['last_message_at' => now()]);
            }

            // Pesan pada percakapan kelas agar daftar obrolan tidak kosong.
            $classConv1 = Conversation::where('type', 'class')->where('course_id', $course1->id)->first();

            if ($classConv1 && ! Message::where('conversation_id', $classConv1->id)->exists()) {
                Message::create([
                    'conversation_id' => $classConv1->id,
                    'sender_id' => $teacher1->id,
                    'body' => 'Selamat datang semua. Gunakan grup ini untuk diskusi kelas.',
                    'type' => 'text',
                ]);

                $classConv1->update(['last_message_at' => now()]);
            }

            /* ---------------------------------------------------------- */
            /* Notifikasi + audit log                                      */
            /* ---------------------------------------------------------- */
            foreach ($course1Students as $studentId) {
                $student = User::find($studentId);

                if ($student->notifications()->exists()) {
                    continue;
                }

                $student->notifications()->create([
                    'id' => (string) \Illuminate\Support\Str::uuid(),
                    'type' => \Illuminate\Notifications\DatabaseNotification::class,
                    'data' => [
                        'title' => 'Kelas baru',
                        'body' => 'Anda terdaftar pada kelas '.$course1->name.'.',
                        'url' => route('courses.show', $course1),
                        'kind' => 'course',
                    ],
                    'read_at' => null,
                ]);
            }

            \App\Models\AuditLog::create([
                'user_id' => $admin->id,
                'action' => 'system.seed',
                'description' => 'Menjalankan seeder demo (data fiktif).',
                'ip_address' => '127.0.0.1',
                'created_at' => now(),
            ]);
        });
    }
}
