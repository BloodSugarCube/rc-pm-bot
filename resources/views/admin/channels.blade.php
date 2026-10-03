@extends('admin.layout')

@section('title', 'Каналы')

@php
    $weekDays = [
        1 => 'Понедельник',
        2 => 'Вторник',
        3 => 'Среда',
        4 => 'Четверг',
        5 => 'Пятница',
        6 => 'Суббота',
        7 => 'Воскресенье',
    ];
@endphp

@section('content')
@push('styles')
<style> main { max-width: 1150px; } .channels-table td, .channels-table th { white-space: normal; } </style>
@endpush
<h1>Каналы Rocket.Chat</h1>
<p class="muted">Отметьте комнаты с опросом — каналы с включённым опросом показываются выше. В «Дневной опрос» включите, нужно ли отправлять дневной опрос и дневное напоминание для канала. В «Теги команд» через запятую укажите теги Rocket.Chat Teams и/или логины пользователей (например <code>@developers, @aleksandrbelyaev</code>) — по ним строится состав для утреннего и дневного напоминаний и дневного опроса. В «Дни отправки» выберите дни недели, в которые каналу отправляются опрос и напоминания (ничего не выбрано — отправка в будние дни как обычно; Ctrl/Cmd-клик или долгое нажатие для выбора нескольких дней). В «Не тегать в напоминаниях» перечислите логины, которых не нужно упоминать в этом канале, даже если они в команде (например <code>@aleksandr, @evgenia</code>). Временные исключения по датам — в разделе «Периоды отсутствий». Список комнат подтягивается с сервера при открытии страницы.</p>
@if(!empty($syncError))
    <p class="error">Ошибка синхронизации: {{ $syncError }}</p>
@endif
<form method="post" action="{{ route('admin.channels.update') }}">
    @csrf
    <div class="card">
        <table class="channels-table">
            <thead>
                <tr>
                    <th>Опрос</th>
                    <th>Дневной опрос</th>
                    <th>Название</th>
                    <th>Дни отправки</th>
                    <th>Теги команд</th>
                    <th>Не тегать в напоминаниях</th>
                    <th>Тип</th>
                </tr>
            </thead>
            <tbody>
                @forelse($channels as $ch)
                    @php $chDays = $ch->pollDays(); @endphp
                    <tr>
                        <td>
                            <input type="checkbox" name="active[]" value="{{ $ch->id }}" @checked($ch->is_poll_active)>
                        </td>
                        <td>
                            <input type="checkbox" name="day_active[]" value="{{ $ch->id }}" @checked($ch->is_day_poll_active)>
                        </td>
                        <td>{{ $ch->name }}</td>
                        <td style="min-width:11rem;">
                            <input type="hidden" name="poll_days_present[]" value="{{ $ch->id }}">
                            <select name="poll_days[{{ $ch->id }}][]" multiple size="5">
                                @foreach($weekDays as $dayNum => $dayName)
                                    <option value="{{ $dayNum }}" @selected(in_array($dayNum, old('poll_days.'.$ch->id, $chDays)))>{{ $dayName }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td style="min-width:14rem;">
                            <input type="text" name="team_tags[{{ $ch->id }}]" value="{{ old('team_tags.'.$ch->id, $ch->team_tags) }}" placeholder="@developers, @aleksandrbelyaev" autocomplete="off">
                        </td>
                        <td style="min-width:14rem;">
                            <input type="text" name="reminder_exclude_tags[{{ $ch->id }}]" value="{{ old('reminder_exclude_tags.'.$ch->id, $ch->reminder_exclude_tags) }}" placeholder="@aleksandr, @evgenia" autocomplete="off">
                        </td>
                        <td>{{ $ch->room_type === 'c' ? 'канал' : ($ch->room_type === 'p' ? 'группа' : 'ЛС') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7">Нет данных. Проверьте ROCKETCHAT_* в .env и права бота.</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($channels->isNotEmpty())
            <p style="margin-top:1rem;"><button type="submit" class="btn">Сохранить</button></p>
        @endif
    </div>
</form>
@endsection