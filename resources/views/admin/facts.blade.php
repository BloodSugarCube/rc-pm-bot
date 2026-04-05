@extends('admin.layout')

@section('title', 'Факты')

@section('content')
<h1>Факты для утреннего опроса</h1>
<p class="muted">Текст вида «Вода безвкусная» подставляется под строку из конфига (по умолчанию «@all Что в работе?»). В календарном году каждый факт используется не более одного раза.</p>

<div class="card">
    <h2 style="font-size:1rem;margin-top:0;">Добавить факт</h2>
    <form method="post" action="{{ route('admin.facts.store') }}">
        @csrf
        <label for="body">Текст факта</label>
        <textarea id="body" name="body" required placeholder="Вода безвкусная">{{ old('body') }}</textarea>
        <p style="margin-top:0.75rem;"><button type="submit" class="btn">Добавить</button></p>
    </form>
</div>

<div class="card">
    <table>
        <thead>
            <tr>
                <th>Текст</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach($facts as $f)
                <tr>
                    <td>{{ $f->body }}</td>
                    <td>
                        <form method="post" action="{{ route('admin.facts.destroy', $f) }}" class="inline" onsubmit="return confirm('Удалить факт?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn danger">Удалить</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
