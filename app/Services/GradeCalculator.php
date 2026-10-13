<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\GradeWeight;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Kalkulator nilai akhir kelas.
 *
 * RUMUS (dokumentasi resmi, diterapkan konsisten di aplikasi):
 *
 *  1. Setiap tugas dinilai dalam skala 0..max_score, lalu dinormalisasi:
 *       n_i = score_i / max_score_i         (0..1)
 *     Rata-rata berbobot tugas memakai `assignments.weight` sebagai bobot poin:
 *       WA = Σ(n_i × weight_i) / Σ(weight_i)
 *
 *  2. Setiap kuis memakai percobaan terbaik (skor tertinggi) yang sudah
 *     dinilai, dinormalisasi terhadap total poin soal:
 *       m_j = best_score_j / total_points_j  (0..1)
 *     WK = Σ(m_j) / jumlah kuis dinilai
 *     (Kuis diberi bobot sama besar di dalam kategorinya.)
 *
 *  3. Kategori yang belum punya komponen bernilai DIKECUALIKAN (bukan 0),
 *     lalu bobot kategori dinormalkan ulang:
 *       Nilai Akhir = (WA × b_Tugas + WK × b_Kuis) / (b_Tugas + b_Kuis) × 100
 *
 *  4. Bila tidak ada komponen bernilai sama sekali → null ("Belum dinilai").
 */
class GradeCalculator
{
    public const DEFAULT_WEIGHTS = [
        GradeWeight::CATEGORY_ASSIGNMENT => 60,
        GradeWeight::CATEGORY_QUIZ => 40,
    ];

    /**
     * Hitung ringkasan nilai satu murid di satu kelas.
     *
     * @return array{
     *   final: float|null,
     *   assignment: array{average: float|null, graded: int, total: int},
     *   quiz: array{average: float|null, graded: int, total: int},
     *   weights: array<string, float>
     * }
     */
    public function forStudent(Course $course, User $student): array
    {
        $weights = $this->weights($course);

        $assignment = $this->assignmentAverage($course, $student);
        $quiz = $this->quizAverage($course, $student);

        $components = [];
        $sumWeight = 0.0;
        $sumValue = 0.0;

        if ($assignment['average'] !== null
            && ($weights[GradeWeight::CATEGORY_ASSIGNMENT] ?? 0) > 0) {
            $w = $weights[GradeWeight::CATEGORY_ASSIGNMENT];
            $components[] = $assignment['average'] * $w;
            $sumWeight += $w;
            $sumValue += $components[count($components) - 1];
        }

        if ($quiz['average'] !== null
            && ($weights[GradeWeight::CATEGORY_QUIZ] ?? 0) > 0) {
            $w = $weights[GradeWeight::CATEGORY_QUIZ];
            $sumWeight += $w;
            $sumValue += $quiz['average'] * $w;
        }

        $final = $sumWeight > 0 ? round(($sumValue / $sumWeight) * 100, 2) : null;

        return [
            'final' => $final,
            'assignment' => $assignment,
            'quiz' => $quiz,
            'weights' => $weights,
        ];
    }

    /**
     * Daftar nilai seluruh murid kelas (dipakai gradebook).
     *
     * @return Collection<int, array{student: User, final: float|null, assignment: array, quiz: array}>
     */
    public function gradebook(Course $course): Collection
    {
        $students = $course->students()->get();

        return $students->map(fn (User $student) => array_merge(
            ['student' => $student],
            $this->forStudent($course, $student),
        ));
    }

    /** @return array<string, float> */
    public function weights(Course $course): array
    {
        $stored = $course->gradeWeights()->pluck('weight', 'category')->all();

        return [
            GradeWeight::CATEGORY_ASSIGNMENT => (float) ($stored[GradeWeight::CATEGORY_ASSIGNMENT]
                ?? self::DEFAULT_WEIGHTS[GradeWeight::CATEGORY_ASSIGNMENT]),
            GradeWeight::CATEGORY_QUIZ => (float) ($stored[GradeWeight::CATEGORY_QUIZ]
                ?? self::DEFAULT_WEIGHTS[GradeWeight::CATEGORY_QUIZ]),
        ];
    }

    /**
     * @return array{average: float|null, graded: int, total: int}
     */
    private function assignmentAverage(Course $course, User $student): array
    {
        $assignments = $course->assignments()
            ->where('status', Assignment::STATUS_PUBLISHED)
            ->with(['submissions' => fn ($q) => $q->where('user_id', $student->id)])
            ->get();

        $total = $assignments->count();

        $weightedSum = 0.0;
        $weightSum = 0.0;
        $graded = 0;

        foreach ($assignments as $assignment) {
            $submission = $assignment->submissions->first();

            if (! $submission || $submission->score === null) {
                continue; // belum dinilai → dikecualikan
            }

            $max = $assignment->max_score > 0 ? $assignment->max_score : 100;
            $normalized = max(0, min(1, $submission->score / $max));
            $w = $assignment->weight > 0 ? $assignment->weight : 1;

            $weightedSum += $normalized * $w;
            $weightSum += $w;
            $graded++;
        }

        return [
            'average' => $weightSum > 0 ? round($weightedSum / $weightSum, 4) : null,
            'graded' => $graded,
            'total' => $total,
        ];
    }

    /**
     * @return array{average: float|null, graded: int, total: int}
     */
    private function quizAverage(Course $course, User $student): array
    {
        $quizzes = $course->quizzes()
            ->where('status', Quiz::STATUS_PUBLISHED)
            ->get();

        $total = $quizzes->count();

        $sum = 0.0;
        $graded = 0;

        foreach ($quizzes as $quiz) {
            $best = $quiz->attempts()
                ->where('user_id', $student->id)
                ->whereIn('status', [QuizAttempt::STATUS_SUBMITTED, QuizAttempt::STATUS_GRADED])
                ->whereNotNull('score')
                ->orderByDesc('score')
                ->first();

            if (! $best) {
                continue; // belum ada percobaan dinilai → dikecualikan
            }

            $maxPoints = (float) $quiz->questions()->sum('points');

            if ($maxPoints <= 0) {
                continue;
            }

            $sum += max(0, min(1, $best->score / $maxPoints));
            $graded++;
        }

        return [
            'average' => $graded > 0 ? round($sum / $graded, 4) : null,
            'graded' => $graded,
            'total' => $total,
        ];
    }
}
