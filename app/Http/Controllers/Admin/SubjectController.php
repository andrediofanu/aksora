<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SubjectController extends Controller
{
    public function __construct()
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
    }

    public function index(Request $request)
    {
        $query = Subject::query()->orderBy('name');

        if ($search = trim((string) $request->query('q'))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        return view('admin.subjects.index', [
            'subjects' => $query->paginate(15)->withQueryString(),
            'search' => $search,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('subjects', 'name')->whereNull('deleted_at')],
            'code' => ['required', 'string', 'max:20', Rule::unique('subjects', 'code')->whereNull('deleted_at'), 'regex:/^[A-Za-z0-9\-]+$/'],
            'description' => ['nullable', 'string', 'max:1000'],
        ], [
            'name.unique' => 'Nama mata pelajaran sudah ada.',
            'code.unique' => 'Kode mata pelajaran sudah ada.',
            'code.regex' => 'Kode hanya boleh berisi huruf, angka, dan tanda hubung.',
        ]);

        $subject = Subject::create($data);

        AuditLogger::log('admin.subject.create', "Membuat mata pelajaran: {$subject->name}", $subject);

        return back()->with('success', 'Mata pelajaran berhasil ditambahkan.');
    }

    public function update(Request $request, Subject $subject)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('subjects', 'name')->whereNull('deleted_at')->ignore($subject->id)],
            'code' => ['required', 'string', 'max:20', Rule::unique('subjects', 'code')->whereNull('deleted_at')->ignore($subject->id), 'regex:/^[A-Za-z0-9\-]+$/'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $subject->update([
            'name' => $data['name'],
            'code' => $data['code'],
            'description' => $data['description'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        AuditLogger::log('admin.subject.update', "Memperbarui mata pelajaran: {$subject->name}", $subject);

        return back()->with('success', 'Mata pelajaran diperbarui.');
    }

    public function destroy(Subject $subject)
    {
        if ($subject->courses()->exists()) {
            return back()->withErrors(['subject' => 'Mata pelajaran masih digunakan oleh kelas.']);
        }

        AuditLogger::log('admin.subject.delete', "Menghapus mata pelajaran: {$subject->name}", $subject);

        // Bebaskan kode agar bisa dipakai ulang (unique di level database).
        $suffix = '-'.$subject->id;
        $subject->code = Str::limit($subject->code, 20 - strlen($suffix), '').$suffix;
        $subject->save();

        $subject->delete();

        return back()->with('success', 'Mata pelajaran dihapus.');
    }
}
