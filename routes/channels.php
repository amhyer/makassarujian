<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('exam.{examId}', function ($user, $examId) {
    // Super Admin and School Admin can always monitor
    if ($user->hasRole(['Super Admin', 'School Admin', 'Proctor'])) {
        return [
            'id'   => $user->id,
            'name' => $user->name,
            'role' => $user->getRoleNames()->first(),
        ];
    }

    // Students must be enrolled as participants in this specific exam
    $isParticipant = \App\Models\ExamParticipant::where('exam_id', $examId)
        ->where('user_id', $user->id)
        ->where('tenant_id', $user->tenant_id) // Tenant isolation
        ->exists();

    if (!$isParticipant) {
        return false; // Reject unauthorized connections
    }

    return [
        'id'   => $user->id,
        'name' => $user->name,
        'role' => 'Student',
    ];
});
