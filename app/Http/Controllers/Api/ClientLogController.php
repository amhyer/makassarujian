<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ClientLogController extends Controller
{
    /**
     * Store frontend logs to client.log
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'level' => 'required|string|in:debug,info,warning,error,critical',
            'message' => 'required|string|max:5000',
            'context' => 'nullable|array',
        ]);

        $level = $validated['level'];
        $message = '[CLIENT] ' . $validated['message'];
        $context = $validated['context'] ?? [];
        
        // Add user info if authenticated
        if (auth()->check()) {
            $context['user_id'] = auth()->id();
        }

        Log::channel('client')->log($level, $message, $context);

        return response()->json(['status' => 'logged']);
    }
}
