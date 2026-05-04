<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreQuestionRequest;
use App\Http\Resources\QuestionResource;
use App\Models\Question;
use App\Models\Subject;
use App\Models\Classes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Cache;

class QuestionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Question::class);

        $tenantId = app('tenant_id');
        $page = $request->get('page', 1);
        $subjectId = $request->get('subject_id', 'all');
        $classId = $request->get('class_id', 'all');
        
        $cacheKey = "tenant_{$tenantId}_questions_p{$page}_s{$subjectId}_c{$classId}";

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($request) {
            $query = Question::query();

            if ($request->has('subject_id')) {
                $query->where('subject_id', $request->subject_id);
            }

            if ($request->has('class_id')) {
                $query->where('class_id', $request->class_id);
            }

            return QuestionResource::collection(
                $query->latest()->paginate(20)
            );
        });
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreQuestionRequest $request)
    {
        Gate::authorize('create', Question::class);

        return DB::transaction(function () use ($request) {
            $content = json_decode($request->content, true);

            $question = Question::create([
                'subject_id' => $request->subject_id,
                'class_id' => $request->class_id,
                'type' => $request->type ?? 'mcq',
                'content' => $content,
                'explanation' => $request->explanation,
                'difficulty' => $request->difficulty,
                'created_by' => Auth::id(),
            ]);

            $this->clearTenantCache();
            return new QuestionResource($question);
        });
    }

    /**
     * Display the specified resource.
     */
    public function show(Question $question)
    {
        Gate::authorize('view', $question);
        
        return new QuestionResource($question);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreQuestionRequest $request, Question $question)
    {
        Gate::authorize('update', $question);
        abort_if($question->tenant_id !== Auth::user()->tenant_id, 403, 'Akses lintas tenant dilarang.');

        return DB::transaction(function () use ($request, $question) {
            $content = json_decode($request->content, true);

            $question->update([
                'subject_id' => $request->subject_id,
                'class_id' => $request->class_id,
                'content' => $content,
                'explanation' => $request->explanation,
                'difficulty' => $request->difficulty,
            ]);

            $this->clearTenantCache();
            return new QuestionResource($question);
        });
    }

    /**
     * Import questions from CSV.
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), 'r');
        
        // Skip header
        $header = fgetcsv($handle, 1000, ',');
        
        $importedCount = 0;
        $errors = [];
        $rowNumber = 1;

        DB::beginTransaction();
        try {
            while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
                $rowNumber++;
                if (count($data) < 8) continue; // Minimum columns

                $subjectName = $data[0];
                $className = $data[1];
                $difficulty = strtolower($data[2]);
                $questionText = $data[3];
                $optionsData = [
                    ['text' => $data[4], 'is_correct' => strtoupper($data[8]) === 'A'],
                    ['text' => $data[5], 'is_correct' => strtoupper($data[8]) === 'B'],
                    ['text' => $data[6], 'is_correct' => strtoupper($data[8]) === 'C'],
                    ['text' => $data[7], 'is_correct' => strtoupper($data[8]) === 'D'],
                ];

                // Lookup Subject
                $subject = Subject::where('name', 'like', $subjectName)
                    ->orWhere('code', 'like', $subjectName)
                    ->first();
                
                // Lookup Class
                $class = Classes::where('name', 'like', $className)
                    ->orWhere('level', $className)
                    ->first();

                if (!$subject || !$class) {
                    $errors[] = "Baris {$rowNumber}: Mata pelajaran atau Kelas tidak ditemukan.";
                    continue;
                }

                Question::create([
                    'subject_id' => $subject->id,
                    'class_id' => $class->id,
                    'type' => 'mcq',
                    'content' => [
                        'question_text' => $questionText,
                        'options' => collect($optionsData)->map(function($opt, $index) {
                            return [
                                'key' => chr(65 + $index),
                                'text' => $opt['text'],
                                'is_correct' => $opt['is_correct']
                            ];
                        })->toArray(),
                        'meta' => ['latex' => true]
                    ],
                    'difficulty' => in_array($difficulty, ['easy', 'medium', 'hard']) ? $difficulty : 'medium',
                    'created_by' => Auth::id(),
                ]);

                $importedCount++;
            }
            
            DB::commit();
            $this->clearTenantCache();

            return response()->json([
                'message' => "Berhasil mengimpor {$importedCount} soal.",
                'errors' => $errors
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal mengimpor file: ' . $e->getMessage()], 500);
        } finally {
            fclose($handle);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Question $question)
    {
        Gate::authorize('delete', $question);
        abort_if($question->tenant_id !== Auth::user()->tenant_id, 403, 'Akses lintas tenant dilarang.');

        $question->delete();

        $this->clearTenantCache();

        return response()->json([
            'message' => 'Soal berhasil dihapus (Soft Delete).'
        ]);
    }

    /**
     * Get statistics for questions distribution.
     */
    public function stats()
    {
        Gate::authorize('viewAny', Question::class);

        $tenantId = app('tenant_id');
        $cacheKey = "tenant_{$tenantId}_question_stats";

        return Cache::remember($cacheKey, now()->addMinutes(30), function () {
            $total = Question::count();
            
            $bySubject = DB::table('questions')
                ->join('subjects', 'questions.subject_id', '=', 'subjects.id')
                ->where('questions.tenant_id', app('tenant_id'))
                ->whereNull('questions.deleted_at')
                ->select('subjects.name', DB::raw('count(*) as total'))
                ->groupBy('subjects.id', 'subjects.name')
                ->get();

            $byDifficulty = Question::select('difficulty', DB::raw('count(*) as total'))
                ->groupBy('difficulty')
                ->get();

            $byClass = DB::table('questions')
                ->join('classes', 'questions.class_id', '=', 'classes.id')
                ->where('questions.tenant_id', app('tenant_id'))
                ->whereNull('questions.deleted_at')
                ->select('classes.name', DB::raw('count(*) as total'))
                ->groupBy('classes.id', 'classes.name')
                ->get();

            return response()->json([
                'total' => $total,
                'by_subject' => $bySubject,
                'by_difficulty' => $byDifficulty,
                'by_class' => $byClass,
            ]);
        });
    }

    protected function clearTenantCache()
    {
        $tenantId = app('tenant_id');
        Cache::forget("tenant_{$tenantId}_question_stats");
    }

    /**
     * Import questions from Excel (.xlsx).
     */
    public function importExcel(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:5120',
            'subject_id' => 'nullable|exists:subjects,id',
            'class_id' => 'nullable|exists:classes,id',
        ]);

        $file = $request->file('file');
        
        \Maatwebsite\Excel\Facades\Excel::queueImport(
            new \App\Imports\QuestionsImport(
                Auth::user()->tenant_id,
                Auth::id(),
                $request->subject_id,
                $request->class_id
            ), 
            $file
        );

        $this->clearTenantCache();

        return response()->json([
            'message' => 'Import sedang diproses di latar belakang. Silakan cek kembali beberapa saat lagi.',
        ]);
    }
}
