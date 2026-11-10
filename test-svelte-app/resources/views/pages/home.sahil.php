@extends('layouts.app')

@section('title')Welcome · {{ app_name ?? 'SahilPHP' }}@endsection

@section('content')
    <main>
        <h1>Welcome to {{ app_name ?? 'SahilPHP' }}</h1>
        <p>Your {{ profile_label ?? 'SahilPHP' }} application is ready.</p>
        <p>Start in <code>routes/web.php</code> and build views in <code>resources/views</code>.</p>
    </main>
@endsection
