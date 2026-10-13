<?php

namespace App\Support;

/**
 * Konstanta peran pengguna. Sumber kebenaran tunggal untuk RBAC di backend.
 */
final class Role
{
    public const ADMIN = 'admin';
    public const TEACHER = 'teacher';
    public const STUDENT = 'student';

    public const ALL = [self::ADMIN, self::TEACHER, self::STUDENT];

    public static function label(string $role): string
    {
        return match ($role) {
            self::ADMIN => 'Admin',
            self::TEACHER => 'Guru',
            self::STUDENT => 'Murid',
            default => 'Tidak dikenal',
        };
    }

    /** Warna badge (komponen <x-badge>) untuk peran. */
    public static function badgeColor(string $role): string
    {
        return match ($role) {
            self::ADMIN => 'red',
            self::TEACHER => 'indigo',
            self::STUDENT => 'green',
            default => 'slate',
        };
    }
}
