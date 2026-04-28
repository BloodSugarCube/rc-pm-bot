@extends('admin.layout')

@section('title', 'Периоды отсутствий')

@section('content')
<h1>Периоды отсутствий</h1>
<p class="muted">В указанный диапазон дней сотрудник не попадает в теги утреннего и дневного напоминания (по логину Rocket.Chat). Таймзона бота: <code>{{ config('bot.timezone') }}</code>.</p>

<div class="card">
    <h2 style="font-size:1rem;margin-top:0;">Добавить период</h2>
    <form method="post" action="{{ route('admin.absence-periods.store') }}" style="display:flex;flex-wrap:wrap;gap:1rem;align-items:flex-end;">
        @csrf
        <div>
            <label for="date_from">С даты</label>
            <input type="date" id="date_from" name="date_from" value="{{ old('date_from') }}" required>
        </div>
        <div>
            <label for="date_to">По дату</label>
            <input type="date" id="date_to" name="date_to" value="{{ old('date_to') }}" required>
        </div>
        <div style="flex:1;min-width:12rem;">
            <label for="employee_tag">Тег сотрудника</label>
            <input type="text" id="employee_tag" name="employee_tag" value="{{ old('employee_tag') }}" placeholder="@aleksandr" required autocomplete="off">
        </div>
        <div>
            <button type="submit" class="btn">Сохранить</button>
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
                <th>С даты</th>
                <th>По дату</th>
                <th>Тег</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($periods as $p)
                <tr>
                    <td>{{ $p->date_from->format('Y-m-d') }}</td>
                    <td>{{ $p->date_to->format('Y-m-d') }}</td>
                    <td>{{ $p->employee_tag }}</td>
                    <td>
                        <form method="post" action="{{ route('admin.absence-periods.destroy', $p) }}" class="inline" onsubmit="return confirm('Удалить запись?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn danger" style="font-size:0.8rem;padding:0.25rem 0.5rem;">Удалить</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="muted">Пока нет записей.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
