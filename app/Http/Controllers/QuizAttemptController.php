<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Services\AuditLogger;
use App\Services\QuizGrader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Pengerjaan kuis oleh murid + penilaian otomatis & manual.
 *
 * Kunci jawaban TIDAK pernah dikirim ke klien sebelum waktu yang
 * diizinkan guru (lihat Quiz::canShowResults).
 */
class QuizAttemptController extends Controller
{
    public function __construct(private QuizGrader $grader)
    {
    }

    /** Mulai percobaan kuis. */
    public function start(Request $request, Course $course, Quiz $quiz)
    {
        abort_if($quiz->course_id !== $course->id, 404);
        abort_unless($request->user()->can('start', $quiz), 403);

        $user = $request->user();

        // Jika masih ada percobaan berjalan, lanjutkan itu.
        $active = $quiz->attempts()
            ->where('user_id', $user->id)
            ->where('status', QuizAttempt::STATUS_IN_PROGRESS)
            ->first();

        if ($active) {
            return redirect()->route('quizzes.take', [$course, $quiz, $active]);
        }

        if ($quiz->questions()->count() === 0) {
            return back()->withErrors(['quiz' => 'Kuis ini belum memiliki soal.']);
        }

        $attempt = DB::transaction(function () use ($quiz, $user) {
            $nextNo = ((int) $quiz->attempts()->where('user_id', $user->id)->max('attempt_no')) + 1;

            return QuizAttempt::create([
                'quiz_id' => $quiz->id,
                'user_id' => $user->id,
                'attempt_no' => $nextNo,
                'started_at' => now(),
                'expires_at' => $quiz->time_limit_minutes
                    ? now()->addMinutes($quiz->time_limit_minutes)
                    : null,
                'status' => QuizAttempt::STATUS_IN_PROGRESS,
            ]);
        });

        AuditLogger::log('quiz.attempt.start', "Mulai mengerjakan kuis: {$quiz->title}", $quiz);

        return redirect()->route('quizzes.take', [$course, $quiz, $attempt]);
    }

    /** Halaman pengerjaan. */
    public function take(Request $request, Course $course, Quiz $quiz, QuizAttempt $attempt)
    {
        abort_if($quiz->course_id !== $course->id, 404);
        abort_if($attempt->quiz_id !== $quiz->id, 404);
        abort_unless($request->user()->can('view', $attempt), 403);
        abort_if($attempt->user_id !== $request->user()->id, 403);

        if ($attempt->isInProgress() && $attempt->isExpired()) {
            $this->grader->grade($attempt);

            return redirect()->route('quizzes.results', [$course, $quiz, $attempt])
                ->with('success', 'Waktu habis. Jawaban Anda telah dikirim otomatis.');
        }

        if (! $attempt->isInProgress()) {
            return redirect()->route('quizzes.results', [$course, $quiz, $attempt]);
        }

        if (! $quiz->isOpenNow()) {
            return redirect()->route('quizzes.show', [$course, $quiz])
                ->withErrors(['quiz' => 'Kuis sudah ditutup.']);
        }

        // Pertanyaan tanpa kunci jawaban dan tanpa penanda opsi benar.
        $questions = $quiz->questions()->with('options')->get();

        $answers = $attempt->answers()->get()->keyBy('question_id');

        return view('quizzes.take', [
            'course' => $course,
            'quiz' => $quiz,
            'attempt' => $attempt,
            'questions' => $questions,
            'answers' => $answers,
            'totalPoints' => $questions->sum('points'),
        ]);
    }

    /** Simpan jawaban per soal (AJAX). */
    public function answer(Request $request, Course $course, Quiz $quiz, QuizAttempt $attempt)
    {
        abort_if($quiz->course_id !== $course->id, 404);
        abort_if($attempt->quiz_id !== $quiz->id, 404);
        abort_unless($request->user()->can('answer', $attempt), 403);

        if ($attempt->isExpired()) {
            $this->grader->grade($attempt);

            throw ValidationException::withMessages([
                'answer' => 'Waktu sudah habis. Jawaban dikirim otomatis.',
            ]);
        }

        $request->validate([
            'question_id' => ['required', 'integer'],
            'option_id' => ['nullable', 'integer'],
            'answer_text' => ['nullable', 'string', 'max:1000'],
        ]);

        $question = QuizQuestion::where('quiz_id', $quiz->id)
            ->find($request->input('question_id'));

        if (! $question) {
            throw ValidationException::withMessages(['question_id' => 'Soal tidak ditemukan.']);
        }

        $optionId = $request->input('option_id');

        if ($question->isMultipleChoice()) {
            if ($optionId === null) {
                throw ValidationException::withMessages(['option_id' => 'Pilih salah satu jawaban.']);
            }

            // Pastikan opsi benar-benar milik soal ini (cegah manipulasi input).
            $belongs = QuizOption::where('question_id', $question->id)
                ->where('id', $optionId)
                ->exists();

            if (! $belongs) {
                throw ValidationException::withMessages(['option_id' => 'Pilihan jawaban tidak valid.']);
            }

            $text = null;
        } else {
            $optionId = null;
            $text = $request->input('answer_text');
        }

        QuizAnswer::updateOrCreate(
            ['attempt_id' => $attempt->id, 'question_id' => $question->id],
            [
                'option_id' => $optionId,
                'answer_text' => $text,
                'is_correct' => null, // dinilai saat pengiriman
                'points_earned' => 0,
                'requires_review' => false,
            ],
        );

        return response()->json(['status' => 'saved', 'saved_at' => now()->toIso8601String()]);
    }

    /** Selesaikan dan nilai kuis. */
    public function submit(Request $request, Course $course, Quiz $quiz, QuizAttempt $attempt)
    {
        abort_if($quiz->course_id !== $course->id, 404);
        abort_if($attempt->quiz_id !== $quiz->id, 404);
        abort_unless($request->user()->can('answer', $attempt), 403);

        if (! $attempt->isExpired()) {
            // Kuis tanpa batas waktu tetap harus dalam jadwal.
            if (! $quiz->isOpenNow()) {
                return redirect()->route('quizzes.show', [$course, $quiz])
                    ->withErrors(['quiz' => 'Kuis sudah ditutup.']);
            }
        }

        $result = $this->grader->grade($attempt);

        AuditLogger::log('quiz.attempt.submit', "Menyelesaikan kuis: {$quiz->title}", $quiz);

        $message = $result['needs_review']
            ? 'Jawaban dikirim. Sebagian jawaban menunggu pemeriksaan guru.'
            : 'Jawaban berhasil dikirim.';

        return redirect()
            ->route('quizzes.results', [$course, $quiz, $attempt])
            ->with('success', $message);
    }

    /** Hasil kuis (sesuai pengaturan guru). */
    public function results(Request $request, Course $course, Quiz $quiz, QuizAttempt $attempt)
    {
        abort_if($quiz->course_id !== $course->id, 404);
        abort_if($attempt->quiz_id !== $quiz->id, 404);
        abort_unless($request->user()->can('view', $attempt), 403);

        $user = $request->user();
        $canManage = $user->can('update', $quiz);

        $allowed = $canManage || $quiz->canShowResults($attempt);

        $answers = $attempt->answers()->with('question.options', 'option')->get()->keyBy('question_id');
        $questions = $quiz->questions()->with('options')->get();

        return view('quizzes.results', [
            'course' => $course,
            'quiz' => $quiz,
            'attempt' => $attempt,
            'answers' => $answers,
            'questions' => $questions,
            'allowed' => $allowed,
            'totalPoints' => $questions->sum('points'),
            'canManage' => $canManage,
        ]);
    }

    /** Penilaian manual jawaban singkat oleh guru. */
    public function manualGrade(Request $request, Course $course, Quiz $quiz, QuizAttempt $attempt)
    {
        abort_if($quiz->course_id !== $course->id, 404);
        abort_if($attempt->quiz_id !== $quiz->id, 404);
        abort_unless($request->user()->can('update', $quiz), 403);

        $request->validate([
            'points' => ['required', 'array'],
            'points.*' => ['numeric', 'min:0', 'max:1000'],
        ]);

        $items = [];
        foreach ($request->input('points') as $questionId => $points) {
            $exists = QuizQuestion::where('quiz_id', $quiz->id)->where('id', $questionId)->exists();
            if ($exists) {
                $items[] = ['id' => (int) $questionId, 'points' => (float) $points];
            }
        }

        if ($items === []) {
            return back()->withErrors(['points' => 'Tidak ada jawaban untuk dinilai.']);
        }

        $this->grader->manualGrade($attempt, $items);

        AuditLogger::log('quiz.manual_grade', "Penilaian manual kuis: {$quiz->title}", $attempt);

        return back()->with('success', 'Nilai manual berhasil disimpan.');
    }
}
