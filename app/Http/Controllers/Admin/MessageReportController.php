<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MessageReport;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

/**
 * Moderasi laporan pesan.
 */
class MessageReportController extends Controller
{
    public function __construct()
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
    }

    public function index(Request $request)
    {
        $status = $request->query('status', 'open');

        $query = MessageReport::with([
            'reporter:id,name,email',
            'message:id,conversation_id,sender_id,body',
            'message.sender:id,name,email',
            'message.conversation:id,type,title',
        ])->orderByDesc('created_at');

        if (in_array($status, ['open', 'resolved', 'dismissed'], true)) {
            $query->where('status', $status);
        }

        return view('admin.message-reports', [
            'reports' => $query->paginate(15),
            'status' => $status,
            'counts' => [
                'open' => MessageReport::where('status', 'open')->count(),
                'resolved' => MessageReport::where('status', 'resolved')->count(),
                'dismissed' => MessageReport::where('status', 'dismissed')->count(),
            ],
        ]);
    }

    public function resolve(Request $request, MessageReport $report)
    {
        $data = $request->validate([
            'status' => ['required', 'in:resolved,dismissed'],
            'resolution_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $report->update([
            'status' => $data['status'],
            'resolved_by' => $request->user()->id,
            'resolution_note' => $data['resolution_note'] ?? null,
        ]);

        AuditLogger::log('admin.report.resolve', "Menindak laporan pesan #{$report->message_id}", $report);

        return back()->with('success', 'Laporan ditandai selesai.');
    }
}
