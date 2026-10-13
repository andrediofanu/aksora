<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function __construct()
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
    }

    public function edit()
    {
        return view('admin.settings', [
            'settings' => Setting::pluck('value', 'key'),
            'logs' => \App\Models\AuditLog::where('action', 'like', 'admin.setting%')
                ->orderByDesc('created_at')
                ->limit(10)
                ->get(),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'school_name' => ['required', 'string', 'max:150'],
            'app_description' => ['nullable', 'string', 'max:300'],
            'allow_student_private_message' => ['required', 'in:1,0'],
            'allow_student_join_by_code' => ['required', 'in:1,0'],
            'max_upload_mb' => ['required', 'integer', 'min:1', 'max:50'],
            'grade_rounding' => ['required', 'in:0,1,2'],
            'registration_open' => ['required', 'in:1,0'],
        ]);

        foreach ($data as $key => $value) {
            Setting::put($key, $value, 'general');
        }

        AuditLogger::log('admin.setting.update', 'Memperbarui pengaturan aplikasi', null, $data);

        return back()->with('success', 'Pengaturan berhasil disimpan.');
    }
}
