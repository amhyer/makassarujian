<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\ExamParticipant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExamParticipantController extends Controller
{
    /**
     * Tampilkan halaman distribusi ujian / assign peserta.
     */
    public function index()
    {
        // Ambil ujian milik tenant saat ini
        $exams = Exam::withCount('participants')->latest()->paginate(15);

        // Ambil semua siswa di tenant ini (Role: Student)
        // Kita anggap role id 4 adalah student, atau gunakan query yang relevan
        // Jika menggunakan Spatie Permission:
        $students = User::whereHas('roles', function($q) {
            $q->where('name', 'Student');
        })->get();

        return view('pages.ujian.distribusi', compact('exams', 'students'));
    }

    /**
     * Proses assign peserta ke ujian.
     */
    public function store(Request $request, Exam $exam)
    {
        $request->validate([
            'student_ids' => 'required|array',
            'student_ids.*' => 'exists:users,id'
        ]);

        DB::transaction(function () use ($request, $exam) {
            // Kita bisa menambahkan yang baru, mengabaikan yang sudah ada
            // atau melakukan sinkronisasi
            
            $existingParticipantIds = $exam->participants()->pluck('user_id')->toArray();
            $newParticipantIds = array_diff($request->student_ids, $existingParticipantIds);

            $insertData = [];
            foreach ($newParticipantIds as $userId) {
                $insertData[] = [
                    'id' => (string) \Illuminate\Support\Str::uuid(),
                    'exam_id' => $exam->id,
                    'user_id' => $userId,
                    'tenant_id' => tenant()->id ?? auth()->user()->tenant_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if (!empty($insertData)) {
                ExamParticipant::insert($insertData);
            }
        });

        return redirect()->back()->with('success', 'Berhasil mendistribusikan / assign siswa ke ujian!');
    }
}
