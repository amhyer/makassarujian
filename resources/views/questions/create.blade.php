@extends('layouts.app')

@section('content')
<div id="app" class="py-4">
    <question-form 
        subject-id="{{ request('subject_id') }}" 
        class-id="{{ request('class_id') }}"
    ></question-form>
</div>
@endsection
