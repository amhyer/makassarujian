<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\ExamParticipant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DistributionController extends Controller
{
    /**
     * Store distributions (assign exam to students)
     */
    public function store(Request $request, Exam $exam)
    {
        abort_if($exam->tenant_id !== Auth::user()->tenant_id, 403);

        $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id'
        ]);

        try {
            DB::beginTransaction();

            $tenantId = Auth::user()->tenant_id;
            $now = now();
            $data = [];

            foreach ($request->user_ids as $userId) {
                // Hindari duplikasi assignment
                $exists = ExamParticipant::where('exam_id', $exam->id)
                            ->where('user_id', $userId)
                            ->exists();
                
                if (!$exists) {
                    $data[] = [
                        'id' => (string) \Illuminate\Support\Str::uuid(),
                        'exam_id' => $exam->id,
                        'user_id' => $userId,
                        'tenant_id' => $tenantId,
                        'created_at' => $now,
                        'updated_at' => $now
                    ];
                }
            }

            if (!empty($data)) {
                ExamParticipant::insert($data);
            }

            DB::commit();

            if ($request->wantsJson()) {
                return response()->json(['message' => 'Ujian berhasil didistribusikan.']);
            }

            return back()->with('success', 'Ujian berhasil didistribusikan kepada peserta yang dipilih.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Distribusi Ujian Gagal: ' . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan sistem saat mendistribusikan ujian.');
        }
    }
}
