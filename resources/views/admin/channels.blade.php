@extends('admin.layout')

@section('title', 'Каналы')

@section('content')
<h1>Каналы Rocket.Chat</h1>
<p class="muted">Отметьте комнаты с опросом — каналы с включённым опросом показываются выше. В «Теги команд» через запятую укажите теги Rocket.Chat Teams (например <code>@developers, @testers</code>) — по ним строится состав для утреннего и дневного напоминаний и дневного опроса. Если команда в RC пуста, токен считается логином пользователя. В «Не тегать в напоминаниях» перечислите логины, которых не нужно упоминать в этом канале, даже если они в команде (например <code>@aleksandr, @evgenia</code>). Временные исключения по датам — в разделе «Периоды отсутствий». Список комнат подтягивается с сервера при открытии страницы.</p>
@if(!empty($syncError))
    <p class="error">Ошибка синхронизации: {{ $syncError }}</p>
@endif
<form method="post" action="{{ route('admin.channels.update') }}">
    @csrf
    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>Опрос</th>
                    <th>Название</th>
                    <th>Теги команд</th>
                    <th>Не тегать в напоминаниях</th>
                    <th>Тип</th>
                    <th class="muted">Rocket room id</th>
                </tr>
            </thead>
            <tbody>
                @forelse($channels as $ch)
                    <tr>
                        <td>
                            <input type="checkbox" name="active[]" value="{{ $ch->id }}" @checked($ch->is_poll_active)>
                        </td>
                        <td>{{ $ch->name }}</td>
                        <td style="min-width:14rem;">
                            <input type="text" name="team_tags[{{ $ch->id }}]" value="{{ old('team_tags.'.$ch->id, $ch->team_tags) }}" placeholder="@developers, @testers" autocomplete="off">
                        </td>
                        <td style="min-width:14rem;">
                            <input type="text" name="reminder_exclude_tags[{{ $ch->id }}]" value="{{ old('reminder_exclude_tags.'.$ch->id, $ch->reminder_exclude_tags) }}" placeholder="@aleksandr, @evgenia" autocomplete="off">
                        </td>
                        <td>{{ $ch->room_type === 'c' ? 'канал' : ($ch->room_type === 'p' ? 'группа' : 'ЛС') }}</td>
                        <td class="muted" style="font-size:0.8rem;">{{ $ch->rocket_room_id }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6">Нет данных. Проверьте ROCKETCHAT_* в .env и права бота.</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($channels->isNotEmpty())
            <p style="margin-top:1rem;"><button type="submit" class="btn">Сохранить</button></p>
        @endif
    </div>
</form>
@endsection
