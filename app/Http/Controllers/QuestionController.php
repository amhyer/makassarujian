<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\Subject;
use App\Models\Classes;
use App\Http\Requests\StoreQuestionRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class QuestionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Question::with(['subject', 'creator']);

        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }

        if ($request->filled('class_id')) {
            $query->where('class_id', $request->class_id);
        }

        if ($request->filled('search')) {
            $query->where('content->question_text', 'like', '%' . $request->search . '%');
        }

        $questions = $query->latest()->paginate(20);
        $subjects = Subject::all();
        $classes = Classes::all();

        return view('questions.index', compact('questions', 'subjects', 'classes'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $subjects = Subject::all();
        $classes = Classes::all();
        
        return view('questions.form', [
            'question' => new Question(),
            'subjects' => $subjects,
            'classes' => $classes,
            'isEdit' => false
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreQuestionRequest $request)
    {

        return DB::transaction(function () use ($request) {
            $content = json_decode($request->content, true);

            Question::create([
                'subject_id' => $request->subject_id,
                'class_id' => $request->class_id,
                'type' => $request->type ?? 'mcq',
                'content' => $content,
                'explanation' => $request->explanation,
                'difficulty' => $request->difficulty,
                'created_by' => Auth::id(),
            ]);

            return redirect()->route('questions.index')->with('success', 'Soal berhasil ditambahkan.');
        });
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Question $question)
    {
        Gate::authorize('update', $question);
        abort_if($question->tenant_id !== Auth::user()->tenant_id, 403, 'Akses lintas tenant dilarang.');

        $subjects = Subject::all();
        $classes = Classes::all();

        return view('questions.form', [
            'question' => $question,
            'subjects' => $subjects,
            'classes' => $classes,
            'isEdit' => true
        ]);
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

            return redirect()->route('questions.index')->with('success', 'Soal berhasil diperbarui.');
        });
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Question $question)
    {
        Gate::authorize('delete', $question);
        abort_if($question->tenant_id !== Auth::user()->tenant_id, 403, 'Akses lintas tenant dilarang.');
        
        $question->delete();

        return redirect()->route('questions.index')->with('success', 'Soal berhasil dihapus.');
    }

    /**
     * Upload image for question.
     */
    public function uploadImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $tenantId = Auth::user()->tenant_id;
        $path = $request->file('image')->store("tenants/{$tenantId}/questions", 'public');

        return response()->json([
            'url' => asset('storage/' . $path)
        ]);
    }
}
