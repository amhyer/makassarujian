<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('exam.{examId}', function ($user, $examId) {
    // Only proctors and students enrolled in this exam can join
    return [
        'id' => $user->id,
        'name' => $user->name,
        'role' => $user->role,
    ];
});
