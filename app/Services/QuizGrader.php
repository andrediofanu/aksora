<?php

namespace App\Services;

use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use Illuminate\Support\Facades\DB;

/**
 * Penilai kuis. Kunci jawaban tidak pernah disentuh di sisi klien;
 * seluruh penilaian terjadi di backend ini.
 */
class QuizGrader
{
    /**
     * Nilai satu percobaan kuis.
     *
     * - Pilihan ganda dinilai otomatis.
     * - Jawaban singkat dicocokkan dengan kunci (opsional case-sensitive);
     *   bila kunci kosong, jawaban ditandai requires_review agar guru menilai manual.
     *
     * @return array{score: float, needs_review: bool}
     */
    public function grade(QuizAttempt $attempt): array
    {
        return DB::transaction(function () use ($attempt) {
            $attempt->loadMissing('quiz.questions.options', 'answers');

            $totalScore = 0.0;
            $needsReview = false;

            /** @var \App\Models\QuizQuestion $question */
            foreach ($attempt->quiz->questions as $question) {
                $answer = $attempt->answers
                    ->first(fn (QuizAnswer $a) => $a->question_id === $question->id);

                if (! $answer) {
                    $answer = QuizAnswer::create([
                        'attempt_id' => $attempt->id,
                        'question_id' => $question->id,
                        'option_id' => null,
                        'answer_text' => null,
                        'is_correct' => false,
                        'points_earned' => 0,
                    ]);
                }

                if ($question->isMultipleChoice()) {
                    $correct = $question->isAnswerCorrect($answer->option_id, null);
                    $earned = $correct ? $question->points : 0;

                    $answer->update([
                        'is_correct' => $correct,
                        'points_earned' => $earned,
                        'requires_review' => false,
                    ]);

                    $totalScore += $earned;
                } else {
                    $answered = trim((string) $answer->answer_text);

                    if ($question->correct_answer === null || trim($question->correct_answer) === '') {
                        // Guru belum menentukan kunci → nilai manual.
                        $answer->update([
                            'is_correct' => null,
                            'points_earned' => 0,
                            'requires_review' => true,
                        ]);
                        $needsReview = true;

                        continue;
                    }

                    $correct = $question->isAnswerCorrect(null, $answered);
                    $earned = $correct ? $question->points : 0;

                    $answer->update([
                        'is_correct' => $correct,
                        'points_earned' => $earned,
                        'requires_review' => false,
                    ]);

                    $totalScore += $earned;
                }
            }

            $score = round($totalScore, 2);

            $attempt->update([
                'score' => $score,
                'submitted_at' => $attempt->submitted_at ?? now(),
                'status' => $needsReview ? QuizAttempt::STATUS_SUBMITTED : QuizAttempt::STATUS_GRADED,
                'needs_manual_review' => $needsReview,
            ]);

            return ['score' => $score, 'needs_review' => $needsReview];
        });
    }

    /**
     * Penilaian manual oleh guru untuk jawaban singkat.
     *
     * @param  array<int, array{id: int, points: float}>  $items
     */
    public function manualGrade(QuizAttempt $attempt, array $items): void
    {
        DB::transaction(function () use ($attempt, $items) {
            foreach ($items as $item) {
                $answer = QuizAnswer::query()
                    ->where('attempt_id', $attempt->id)
                    ->where('question_id', $item['id'])
                    ->first();

                if (! $answer) {
                    continue;
                }

                $question = $answer->question;
                $max = (float) $question->points;
                $given = max(0, min($max, (float) $item['points']));

                $answer->update([
                    'points_earned' => $given,
                    'requires_review' => false,
                    'is_correct' => $given >= $max,
                ]);
            }

            $total = (float) $attempt->answers()->sum('points_earned');

            $attempt->update([
                'score' => round($total, 2),
                'status' => QuizAttempt::STATUS_GRADED,
                'needs_manual_review' => false,
                'submitted_at' => $attempt->submitted_at ?? now(),
            ]);
        });
    }
}
