@extends('layouts.app')

@section('title')Server Error · {{ app_name ?? 'SahilPHP' }}@endsection

@section('content')
    <div class="container py-5 text-center">
        <h1 class="mb-4">500 — Server Error</h1>
        <p class="lead">Something went wrong on our end.</p>
        <a href="/" class="btn btn-primary">Go home</a>
    </div>
@endsection