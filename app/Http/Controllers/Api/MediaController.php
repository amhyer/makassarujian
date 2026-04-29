<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaController extends Controller
{
    public function uploadImage(Request $request)
    {
        $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ]);

        $tenantId = app('tenant_id');
        
        if (!$tenantId) {
            return response()->json(['message' => 'Tenant tidak teridentifikasi.'], 403);
        }

        $file = $request->file('image');
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        
        $path = $file->storeAs(
            "tenants/{$tenantId}/questions",
            $filename,
            'public'
        );

        return response()->json([
            'url' => Storage::url($path),
            'filename' => $filename
        ]);
    }
}
