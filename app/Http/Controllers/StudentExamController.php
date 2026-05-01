<?php

namespace App\Http\Controllers;

use App\Models\Attempt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentExamController extends Controller
{
    /**
     * Tampilkan halaman lobi/persiapan ujian (Diakses dari Dasbor)
     */
    public function lobby($examId)
    {
        $exam = \App\Models\Exam::findOrFail($examId);
        
        abort_if($exam->tenant_id !== Auth::user()->tenant_id, 403, 'Akses ditolak.');

        $isParticipant = \App\Models\ExamParticipant::where('exam_id', $exam->id)
            ->where('user_id', Auth::id())
            ->exists();
            
        abort_if(!$isParticipant, 403, 'Anda tidak terdaftar dalam ujian ini.');

        // Cek apakah sudah ada attempt
        $attempt = Attempt::where('user_id', Auth::id())
                          ->where('exam_id', $exam->id)
                          ->first();

        if ($attempt && $attempt->status === 'completed') {
            return redirect()->route('siswa.dashboard')->with('info', 'Anda sudah menyelesaikan ujian ini.');
        }

        return view('pages.ujian.lobby', compact('exam', 'attempt'));
    }

    /**
     * Start exam (Create attempt) dari tombol di Lobi
     */
    public function start(Request $request, \App\Models\Exam $exam)
    {
        abort_if($exam->tenant_id !== Auth::user()->tenant_id, 403, 'Akses ditolak.');

        $isParticipant = \App\Models\ExamParticipant::where('exam_id', $exam->id)
            ->where('user_id', Auth::id())
            ->exists();
            
        abort_if(!$isParticipant, 403, 'Anda tidak terdaftar dalam ujian ini.');

        $now = now();
        if ($exam->start_at && $now->lt($exam->start_at)) {
            return back()->with('error', 'Ujian belum dimulai.');
        }
        if ($exam->end_at && $now->gt($exam->end_at)) {
            return back()->with('error', 'Waktu ujian telah berakhir.');
        }

        $attempt = Attempt::where('exam_id', $exam->id)
            ->where('user_id', Auth::id())
            ->first();

        if (!$attempt) {
            $attempt = Attempt::create([
                'exam_id' => $exam->id,
                'user_id' => Auth::id(),
                'tenant_id' => Auth::user()->tenant_id,
                'status' => 'ongoing',
                'answers' => [],
                'score' => 0,
                'started_at' => $now,
                'expires_at' => $now->copy()->addMinutes($exam->duration_minutes),
                'device_info' => [
                    'user_agent' => $request->userAgent(),
                    'ip' => $request->ip()
                ]
            ]);
        }

        if ($attempt->status === 'completed') {
            return redirect()->route('siswa.dashboard')->with('info', 'Anda sudah menyelesaikan ujian ini.');
        }

        return redirect()->route('ujian.kerjakan', $attempt->id);
    }



    /**
     * Tampilkan antarmuka pengerjaan ujian
     */
    public function kerjakan(Attempt $attempt)
    {
        // 1. Otorisasi
        abort_if($attempt->user_id !== Auth::id(), 403, 'Anda tidak berhak mengakses sesi ini.');
        abort_if($attempt->tenant_id !== Auth::user()->tenant_id, 403, 'Akses ditolak.');
        
        // Jika sudah selesai, redirect ke hasil
        if ($attempt->status === 'completed') {
            return redirect()->route('siswa.dashboard')->with('info', 'Ujian telah selesai.');
        }

        // 2. Load relasi yang diperlukan
        $attempt->load(['exam.questions']);

        // 3. Format soal untuk frontend
        $questions = $attempt->exam->questions->map(function ($q) {
            $options = [];
            if (is_array($q->options)) {
                foreach ($q->options as $opt) {
                    $options[$opt['key']] = $opt['text'];
                }
            }
            return [
                'id' => $q->id,
                'text' => $q->content ?? '',
                'options' => $options
            ];
        })->values()->toArray();

        return view('pages.ujian.pengerjaan', compact('attempt', 'questions'));
    }
}
