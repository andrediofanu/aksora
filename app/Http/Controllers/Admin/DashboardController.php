<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct()
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
    }

    public function __invoke(Request $request)
    {
        $monthStart = now()->startOfMonth();

        return view('admin.dashboard', [
            'stats' => [
                'users' => User::whereNull('deleted_at')->count(),
                'teachers' => User::whereNull('deleted_at')->where('role', 'teacher')->count(),
                'students' => User::whereNull('deleted_at')->where('role', 'student')->count(),
                'inactive' => User::whereNull('deleted_at')->where('status', 'inactive')->count(),
                'courses' => Course::where('status', 'active')->count(),
                'archived' => Course::where('status', 'archived')->count(),
                'subjects' => \App\Models\Subject::count(),
                'academicYears' => AcademicYear::count(),
            ],
            'roleDistribution' => User::whereNull('deleted_at')
                ->selectRaw('role, COUNT(*) as total')
                ->groupBy('role')
                ->pluck('total', 'role'),
            'recentLogs' => \App\Models\AuditLog::with('user:id,name,role')
                ->orderByDesc('created_at')
                ->limit(12)
                ->get(),
            'newUsers' => User::whereNull('deleted_at')
                ->where('created_at', '>=', $monthStart)
                ->count(),
            'activeAcademicYear' => AcademicYear::where('is_active', true)->first(),
        ]);
    }
}
