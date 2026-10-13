<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\LearningMaterial;
use App\Models\Message;
use App\Models\Quiz;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Unduhan lampiran. Setiap akses diperiksa otorisasinya (anti-IDOR),
 * file tersimpan pada disk privat.
 */
class AttachmentController extends Controller
{
    public function download(Request $request, Attachment $attachment)
    {
        abort_unless($request->user(), 401);

        $this->authorizeAccess($request->user(), $attachment);

        $disk = Storage::disk($attachment->disk);

        abort_unless($disk->exists($attachment->path), 404);

        AuditLogger::log(
            'attachment.download',
            'Mengunduh: '.$attachment->original_name,
            $attachment,
        );

        return $disk->download($attachment->path, $attachment->original_name, [
            'Content-Type' => $attachment->mime_type,
        ]);
    }

    private function authorizeAccess(User $user, Attachment $attachment): void
    {
        $owner = $attachment->attachable;

        $allowed = match (true) {
            $owner instanceof LearningMaterial => $user->can('view', $owner),
            $owner instanceof Assignment => $this->assignmentAccess($user, $owner),
            $owner instanceof AssignmentSubmission => $this->submissionAccess($user, $owner),
            $owner instanceof Message => $user->can('view', $owner->conversation),
            $owner instanceof Quiz => $user->can('view', $owner),
            default => $user->isAdmin(),
        };

        abort_unless($allowed, 403, 'Anda tidak memiliki akses ke berkas ini.');
    }

    private function assignmentAccess(User $user, Assignment $assignment): bool
    {
        if ($user->can('viewSubmissions', $assignment)) {
            return true;
        }

        if (! $assignment->isPublished()) {
            return false;
        }

        // Murid anggota kelas: lampiran instruksi tugas.
        return $user->can('view', $assignment->course);
    }

    private function submissionAccess(User $user, AssignmentSubmission $submission): bool
    {
        return $submission->user_id === $user->id
            || $user->can('viewSubmissions', $submission->assignment);
    }
}
