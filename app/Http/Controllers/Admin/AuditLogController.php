<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function __construct()
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
    }

    public function index(Request $request)
    {
        $query = AuditLog::with('user:id,name,email,role')->orderByDesc('created_at');

        if ($action = trim((string) $request->query('action'))) {
            $query->where('action', 'like', $action.'%');
        }

        if ($userId = $request->query('user_id')) {
            $query->where('user_id', (int) $userId);
        }

        if ($search = trim((string) $request->query('q'))) {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('action', 'like', "%{$search}%");
            });
        }

        if ($from = $request->query('from')) {
            $query->where('created_at', '>=', $from);
        }
        if ($to = $request->query('to')) {
            $query->where('created_at', '<=', $to.' 23:59:59');
        }

        $actions = AuditLog::select('action')->distinct()->orderBy('action')->pluck('action');

        return view('admin.audit-logs', [
            'logs' => $query->paginate(25)->withQueryString(),
            'actions' => $actions,
            'filters' => [
                'action' => $action,
                'q' => $search,
                'from' => $request->query('from'),
                'to' => $request->query('to'),
                'user_id' => $request->query('user_id'),
            ],
        ]);
    }
}
