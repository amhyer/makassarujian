<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\Subject;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ExamController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $tenantId = Auth::user()->tenant_id;
        $exams = Exam::with(['subject'])->where('tenant_id', $tenantId)->latest()->paginate(15);
        
        // Asumsi UI menggunakan Inertia atau view standar. 
        // Sementara return view statis atau json fallback
        if (request()->wantsJson()) {
            return response()->json($exams);
        }

        return view('pages.ujian.index', compact('exams'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $tenantId = Auth::user()->tenant_id;
        $subjects = Subject::where('tenant_id', $tenantId)->get();
        // Fallback view
        return view('pages.ujian.create', compact('subjects'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'subject_id' => 'required|exists:subjects,id',
            'duration_minutes' => 'required|integer|min:1',
            'start_at' => 'nullable|date',
            'end_at' => 'nullable|date|after_or_equal:start_at',
            'shuffle_questions' => 'boolean',
            'shuffle_options' => 'boolean',
            'question_ids' => 'nullable|array',
            'question_ids.*' => 'exists:questions,id'
        ]);

        try {
            DB::beginTransaction();

            $exam = Exam::create([
                'title' => $request->title,
                'description' => $request->description,
                'subject_id' => $request->subject_id,
                'duration_minutes' => $request->duration_minutes,
                'start_at' => $request->start_at,
                'end_at' => $request->end_at,
                'shuffle_questions' => $request->boolean('shuffle_questions', false),
                'shuffle_options' => $request->boolean('shuffle_options', false),
                'tenant_id' => Auth::user()->tenant_id,
                'created_by' => Auth::id(),
            ]);

            if ($request->has('question_ids') && is_array($request->question_ids)) {
                $pivotData = [];
                foreach ($request->question_ids as $index => $qId) {
                    $pivotData[$qId] = ['order' => $index + 1];
                }
                $exam->questions()->sync($pivotData);
            }

            DB::commit();

            if ($request->wantsJson()) {
                return response()->json(['message' => 'Ujian berhasil dibuat', 'exam' => $exam]);
            }

            return redirect()->route('exams.index')->with('success', 'Ujian berhasil dibuat.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal membuat ujian: ' . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan sistem.');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Exam $exam)
    {
        abort_if($exam->tenant_id !== Auth::user()->tenant_id, 403);
        $exam->load('questions');
        
        if (request()->wantsJson()) {
            return response()->json($exam);
        }
        
        return view('pages.ujian.show', compact('exam'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Exam $exam)
    {
        abort_if($exam->tenant_id !== Auth::user()->tenant_id, 403);
        
        $tenantId = Auth::user()->tenant_id;
        $subjects = Subject::where('tenant_id', $tenantId)->get();
        $exam->load('questions');
        
        return view('pages.ujian.edit', compact('exam', 'subjects'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Exam $exam)
    {
        abort_if($exam->tenant_id !== Auth::user()->tenant_id, 403);

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'subject_id' => 'required|exists:subjects,id',
            'duration_minutes' => 'required|integer|min:1',
            'start_at' => 'nullable|date',
            'end_at' => 'nullable|date|after_or_equal:start_at',
            'shuffle_questions' => 'boolean',
            'shuffle_options' => 'boolean',
            'question_ids' => 'nullable|array',
            'question_ids.*' => 'exists:questions,id'
        ]);

        try {
            DB::beginTransaction();

            $exam->update([
                'title' => $request->title,
                'description' => $request->description,
                'subject_id' => $request->subject_id,
                'duration_minutes' => $request->duration_minutes,
                'start_at' => $request->start_at,
                'end_at' => $request->end_at,
                'shuffle_questions' => $request->boolean('shuffle_questions', false),
                'shuffle_options' => $request->boolean('shuffle_options', false),
            ]);

            if ($request->has('question_ids')) {
                $pivotData = [];
                foreach ($request->question_ids as $index => $qId) {
                    $pivotData[$qId] = ['order' => $index + 1];
                }
                $exam->questions()->sync($pivotData);
            }

            DB::commit();

            if ($request->wantsJson()) {
                return response()->json(['message' => 'Ujian berhasil diperbarui', 'exam' => $exam]);
            }

            return redirect()->route('exams.index')->with('success', 'Ujian berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal update ujian: ' . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan sistem.');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Exam $exam)
    {
        abort_if($exam->tenant_id !== Auth::user()->tenant_id, 403);
        $exam->delete();

        if (request()->wantsJson()) {
            return response()->json(['message' => 'Ujian berhasil dihapus']);
        }

        return redirect()->route('exams.index')->with('success', 'Ujian berhasil dihapus.');
    }
}
