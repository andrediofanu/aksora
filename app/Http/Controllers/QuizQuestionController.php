<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreQuestionRequest;
use App\Models\Course;
use App\Models\Quiz;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * CRUD soal kuis. Kunci jawaban hanya disimpan di backend.
 */
class QuizQuestionController extends Controller
{
    public function store(StoreQuestionRequest $request, Course $course, Quiz $quiz)
    {
        abort_if($quiz->course_id !== $course->id, 404);
        abort_unless($request->user()->can('update', $quiz), 403);

        $data = $request->validated();

        DB::transaction(function () use ($data, $quiz, $request) {
            $lastOrder = (int) $quiz->questions()->max('sort_order');

            $question = QuizQuestion::create([
                'quiz_id' => $quiz->id,
                'type' => $data['type'],
                'question' => $data['question'],
                'points' => $data['points'],
                'sort_order' => $lastOrder + 1,
                'correct_answer' => $data['type'] === 'short_answer'
                    ? ($data['correct_answer'] ?? null)
                    : null,
                'case_sensitive' => $request->boolean('case_sensitive'),
            ]);

            if ($data['type'] === 'multiple_choice') {
                $correctIndex = (int) ($data['correct_option'] ?? -1);

                foreach (array_values($data['options']) as $index => $text) {
                    QuizOption::create([
                        'question_id' => $question->id,
                        'option_text' => $text,
                        'is_correct' => $index === $correctIndex,
                        'sort_order' => $index + 1,
                    ]);
                }
            }
        });

        AuditLogger::log('quiz.question.create', "Menambahkan soal pada kuis: {$quiz->title}", $quiz);

        return back()->with('success', 'Soal berhasil ditambahkan.');
    }

    public function update(StoreQuestionRequest $request, Course $course, Quiz $quiz, QuizQuestion $question)
    {
        abort_if($quiz->course_id !== $course->id, 404);
        abort_if($question->quiz_id !== $quiz->id, 404);
        abort_unless($request->user()->can('update', $quiz), 403);

        $data = $request->validated();

        DB::transaction(function () use ($data, $question, $request) {
            $question->update([
                'type' => $data['type'],
                'question' => $data['question'],
                'points' => $data['points'],
                'correct_answer' => $data['type'] === 'short_answer'
                    ? ($data['correct_answer'] ?? null)
                    : null,
                'case_sensitive' => $request->boolean('case_sensitive'),
            ]);

            if ($data['type'] === 'multiple_choice') {
                $correctIndex = (int) ($data['correct_option'] ?? -1);

                // Ganti seluruh opsi agar konsisten dengan input.
                $question->options()->delete();

                foreach (array_values($data['options']) as $index => $text) {
                    QuizOption::create([
                        'question_id' => $question->id,
                        'option_text' => $text,
                        'is_correct' => $index === $correctIndex,
                        'sort_order' => $index + 1,
                    ]);
                }
            } else {
                $question->options()->delete();
            }
        });

        AuditLogger::log('quiz.question.update', "Mengubah soal kuis: {$quiz->title}", $question);

        return back()->with('success', 'Soal berhasil diperbarui.');
    }

    public function destroy(Course $course, Quiz $quiz, QuizQuestion $question)
    {
        abort_if($quiz->course_id !== $course->id, 404);
        abort_if($question->quiz_id !== $quiz->id, 404);
        abort_unless(auth()->user()->can('update', $quiz), 403);

        $question->delete();

        AuditLogger::log('quiz.question.delete', "Menghapus soal kuis: {$quiz->title}", $question);

        return back()->with('success', 'Soal berhasil dihapus.');
    }

    /** Ubah urutan soal. */
    public function reorder(Request $request, Course $course, Quiz $quiz)
    {
        abort_if($quiz->course_id !== $course->id, 404);
        abort_unless($request->user()->can('update', $quiz), 403);

        $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['integer', 'exists:quiz_questions,id'],
        ]);

        foreach (array_values($request->input('order')) as $index => $id) {
            QuizQuestion::where('id', $id)
                ->where('quiz_id', $quiz->id)
                ->update(['sort_order' => $index + 1]);
        }

        return back()->with('success', 'Urutan soal diperbarui.');
    }
}
