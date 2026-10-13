<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Course;

/**
 * Label & warna badge untuk status kelas (satu sumber kebenaran di UI).
 */
final class CourseStatus
{
    public static function label(string $status): string
    {
        return match ($status) {
            Course::STATUS_ACTIVE => 'Aktif',
            Course::STATUS_ARCHIVED => 'Diarsipkan',
            default => 'Tidak dikenal',
        };
    }

    public static function badgeColor(string $status): string
    {
        return match ($status) {
            Course::STATUS_ACTIVE => 'green',
            Course::STATUS_ARCHIVED => 'slate',
            default => 'slate',
        };
    }
}
