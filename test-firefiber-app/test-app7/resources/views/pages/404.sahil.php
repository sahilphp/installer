@extends('layouts.app')

@section('title')Not Found · {{ app_name ?? 'SahilPHP' }}@endsection

@section('content')
    <div class="container py-5 text-center">
        <h1 class="mb-4">404 — Page Not Found</h1>
        <p class="lead">The page you are looking for does not exist.</p>
        <a href="/" class="btn btn-primary">Go home</a>
    </div>
@endsection