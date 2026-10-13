<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AcademicYearController extends Controller
{
    public function __construct()
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
    }

    public function index(Request $request)
    {
        return view('admin.academic-years.index', [
            'years' => AcademicYear::orderByDesc('starts_on')->paginate(10),
            'activeCount' => AcademicYear::where('is_active', true)->count(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:60', Rule::unique('academic_years', 'name')->whereNull('deleted_at')],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after:starts_on'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'in:0,1,on,true,false'],
        ], [
            'name.unique' => 'Nama tahun ajaran sudah ada.',
            'ends_on.after' => 'Tanggal selesai harus setelah tanggal mulai.',
        ]);

        $isActive = $request->boolean('is_active');

        $year = DB::transaction(function () use ($data, $isActive) {
            $year = AcademicYear::create([
                'name' => $data['name'],
                'starts_on' => $data['starts_on'],
                'ends_on' => $data['ends_on'],
                'description' => $data['description'] ?? null,
                'is_active' => $isActive,
            ]);

            if ($isActive) {
                AcademicYear::where('id', '!=', $year->id)->update(['is_active' => false]);
            }

            return $year;
        });

        AuditLogger::log('admin.year.create', "Membuat tahun ajaran: {$year->name}", $year);

        $message = $year->is_active
            ? "Tahun ajaran {$year->name} berhasil ditambahkan dan diaktifkan."
            : 'Tahun ajaran berhasil ditambahkan.';

        return back()->with('success', $message);
    }

    public function activate(AcademicYear $academicYear)
    {
        DB::transaction(function () use ($academicYear) {
            AcademicYear::query()->update(['is_active' => false]);
            $academicYear->update(['is_active' => true]);
        });

        AuditLogger::log('admin.year.activate', "Mengaktifkan tahun ajaran: {$academicYear->name}", $academicYear);

        return back()->with('success', "Tahun ajaran {$academicYear->name} diaktifkan.");
    }

    public function update(Request $request, AcademicYear $academicYear)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:60', Rule::unique('academic_years', 'name')->whereNull('deleted_at')->ignore($academicYear->id)],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after:starts_on'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $academicYear->update($data);

        AuditLogger::log('admin.year.update', "Memperbarui tahun ajaran: {$academicYear->name}", $academicYear);

        return back()->with('success', 'Tahun ajaran diperbarui.');
    }

    public function destroy(AcademicYear $academicYear)
    {
        if ($academicYear->courses()->exists()) {
            return back()->withErrors(['year' => 'Tahun ajaran masih digunakan oleh kelas.']);
        }

        $wasActive = $academicYear->is_active;

        DB::transaction(function () use ($academicYear, $wasActive) {
            // Bebaskan nama agar bisa dipakai ulang (unique di level database).
            $suffix = '#'.$academicYear->id;
            $academicYear->name = Str::limit($academicYear->name, 60 - strlen($suffix), '').$suffix;
            $academicYear->save();

            $academicYear->delete();

            // Aktifkan kembali tahun terbaru lain agar selalu ada tahun aktif.
            if ($wasActive) {
                $next = AcademicYear::orderByDesc('starts_on')->first();
                $next?->update(['is_active' => true]);
            }
        });

        return back()->with('success', 'Tahun ajaran dihapus.');
    }
}
