<?php

namespace App\Services;

use App\Models\Attachment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Penyimpanan lampiran pada disk privat (bukan publik).
 * Nama file dibuat acak sehingga tidak menebak lokasi file lain.
 */
class AttachmentService
{
    /** Ekstensi yang diizinkan secara global. */
    public const ALLOWED_MIMES = [
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv',
        'png', 'jpg', 'jpeg', 'gif', 'webp', 'zip', 'rar',
        'mp3', 'wav', 'mp4', 'webm',
    ];

    public const MAX_SIZE = 10 * 1024 * 1024; // 10 MB

    /**
     * Simpan satu lampiran untuk model tertentu.
     *
     * @param  array<string, mixed>  $options
     * @throws ValidationException
     */
    public function store(Model $model, UploadedFile $file, ?int $uploaderId = null, array $options = []): Attachment
    {
        $allowed = $options['allowed_extensions'] ?? self::ALLOWED_MIMES;
        $maxSize = $options['max_size'] ?? self::MAX_SIZE;

        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension());

        if (! in_array($extension, array_map('strtolower', $allowed), true)) {
            throw ValidationException::withMessages([
                'file' => 'Jenis file .'.$extension.' tidak diizinkan. Yang diizinkan: '.implode(', ', $allowed).'.',
            ]);
        }

        if ($file->getSize() > $maxSize) {
            throw ValidationException::withMessages([
                'file' => 'Ukuran file maksimal '.round($maxSize / 1024 / 1024, 1).' MB.',
            ]);
        }

        // Validasi MIME berdasarkan konten file, bukan ekstensi semata.
        $guessed = $file->getMimeType();
        $trusted = $file->getMimeType();

        if (! $this->mimeAllowed($guessed, $allowed)) {
            throw ValidationException::withMessages([
                'file' => 'Konten file tidak sesuai dengan jenis file yang diizinkan.',
            ]);
        }

        $disk = $options['disk'] ?? 'local';
        $directory = $options['directory'] ?? 'attachments/'.now()->format('Y/m');
        $filename = Str::random(40).'.'.($extension ?: 'bin');
        $path = $file->storeAs($directory, $filename, $disk);

        if ($path === false) {
            throw ValidationException::withMessages([
                'file' => 'Gagal menyimpan file. Coba lagi.',
            ]);
        }

        return Attachment::create([
            'attachable_type' => $model->getMorphClass(),
            'attachable_id' => $model->getKey(),
            'uploader_id' => $uploaderId,
            'disk' => $disk,
            'path' => $path,
            'original_name' => Str::limit($file->getClientOriginalName(), 240, ''),
            'mime_type' => $trusted,
            'size' => (int) $file->getSize(),
        ]);
    }

    /** Hapus file fisik beserta record. */
    public function delete(Attachment $attachment): void
    {
        Storage::disk($attachment->disk)->delete($attachment->path);
        $attachment->delete();
    }

    private function mimeAllowed(?string $mime, array $allowed): bool
    {
        if ($mime === null) {
            return true;
        }

        $map = [
            'application/pdf' => 'pdf',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'application/vnd.ms-excel' => 'xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
            'application/vnd.ms-powerpoint' => 'ppt',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
            'text/plain' => 'txt',
            'text/csv' => 'csv',
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'application/zip' => 'zip',
            'application/x-rar-compressed' => 'rar',
            'application/vnd.rar' => 'rar',
            'audio/mpeg' => 'mp3',
            'audio/wav' => 'wav',
            'audio/x-wav' => 'wav',
            'video/mp4' => 'mp4',
            'video/webm' => 'webm',
        ];

        $expected = $map[$mime] ?? null;

        if ($expected === null) {
            // MIME tidak dikenal: tolak agar tidak lolos sebagai file berbahaya.
            return false;
        }

        return in_array($expected, array_map('strtolower', $allowed), true);
    }
}
