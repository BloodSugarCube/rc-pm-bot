@extends('admin.layout')

@section('title', 'Вход')

@section('content')
<div class="card" style="max-width: 400px; margin: 2rem auto;">
    <h1>Вход в админ-панель</h1>
    @if ($errors->any())
        @foreach ($errors->all() as $err)
            <p class="error">{{ $err }}</p>
        @endforeach
    @endif
    <form method="post" action="{{ route('admin.login.post') }}">
        @csrf
        <label for="username">Логин</label>
        <input id="username" name="username" type="text" value="{{ old('username') }}" required autocomplete="username">
        <label for="password">Пароль</label>
        <input id="password" name="password" type="password" required autocomplete="current-password">
        <p style="margin-top: 1rem;">
            <button type="submit" class="btn">Войти</button>
        </p>
    </form>
    <p class="muted" style="margin-top:1rem;">Учётные данные задаются в .env: BOT_ADMIN_USERNAME, BOT_ADMIN_PASSWORD</p>
</div>
@endsection
