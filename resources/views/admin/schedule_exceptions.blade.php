@extends('admin.layout')

@section('title', 'Дни исключений')

@section('content')
<h1>Дни исключений</h1>
<p class="muted">Для выбранной даты можно запретить рассылку опросов и экспорт или, наоборот, разрешить отправку в выходной. Учитывается таймзона бота (<code>{{ config('bot.timezone') }}</code>).</p>

<div class="card">
    <h2 style="font-size:1rem;margin-top:0;">Добавить день</h2>
    <form method="post" action="{{ route('admin.schedule-exceptions.store') }}" style="display:flex;flex-wrap:wrap;gap:1rem;align-items:flex-end;">
        @csrf
        <div>
            <label for="exception_date">Дата</label>
            <input type="date" id="exception_date" name="exception_date" value="{{ old('exception_date') }}" required>
        </div>
        <div>
            <label for="send_polls">Рассылка</label>
            <select id="send_polls" name="send_polls" required>
                <option value="0" @selected(old('send_polls', '0') === '0')>Не отправлять опросы и экспорт</option>
                <option value="1" @selected(old('send_polls') === '1')>Отправлять опросы (в т.ч. в выходной)</option>
            </select>
        </div>
        <div>
            <button type="submit" class="btn">Добавить</button>
        </div>
    </form>
    @if($errors->any())
        @foreach($errors->all() as $err)
            <p class="error">{{ $err }}</p>
        @endforeach
    @endif
</div>

<div class="card">
    <table>
        <thead>
            <tr>
                <th>Дата</th>
                <th>Поведение</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($exceptions as $ex)
                <tr>
                    <td>{{ $ex->exception_date->format('Y-m-d') }}</td>
                    <td>{{ $ex->send_polls ? 'Отправлять опросы' : 'Не отправлять' }}</td>
                    <td>
                        <form method="post" action="{{ route('admin.schedule-exceptions.destroy', $ex) }}" class="inline" onsubmit="return confirm('Удалить запись?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn danger" style="font-size:0.8rem;padding:0.25rem 0.5rem;">Удалить</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="3" class="muted">Пока нет записей.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
