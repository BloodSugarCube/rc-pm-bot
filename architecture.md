# Архитектура

## Стек

- **Laravel 13**, PHP 8.3, Blade (без SPA-фреймворков, стили инлайн в layout)
- БД: MySQL (или SQLite в локальной разработке), миграции в `database/migrations`
- HTTP к Rocket.Chat: Guzzle (`app/Components/RocketChat`)
- Аутентификация админки: собственный middleware по конфигу (`BOT_ADMIN_*`), сессии

## Слои

```
routes/web.php                     ── маршруты админки (/admin/*)
bootstrap/app.php                  ── регистрация middleware (admin.auth) и расписания
app/Console/Commands/Bot*Command   ── 5 artisan-команд (обёртки над сервисом)
app/Services/DailyPollService      ── вся бизнес-логика опросов, напоминаний, экспорта
app/Components/RocketChat/         ── REST-клиент Rocket.Chat (контракт + реализация + исключение)
app/Models/                        ── Eloquent-модели
app/Http/Controllers/Admin/        ── контроллеры админки
resources/views/admin/             ── Blade-шаблоны админки
```

Планировщик (`bootstrap/app.php`) ежедневно запускает: `bot:morning-poll` → `bot:morning-reminder` → `bot:day-poll` → `bot:day-reminder` → `bot:export-messages`; время и таймзона — в `config/bot.php`.

## Модель данных

| Таблица | Назначение |
| --- | --- |
| `poll_channels` | комнаты Rocket.Chat: `is_poll_active`, `is_day_poll_active`, `team_tags`, `poll_days` (JSON, дни недели), `reminder_exclude_tags` |
| `daily_poll_sessions` | состояние рассылки на день: уникальный индекс `(poll_channel_id, poll_date)`, id утреннего/дневного сообщения, флаги `morning_reminder_sent`, `day_poll_sent`, `day_reminder_sent` |
| `facts`, `fact_usages` | факты и отметки использования (один факт — один раз в год) |
| `poll_schedule_exceptions` | «Дни исключений»: `exception_date` + `send_polls` (запретить/разрешить) |
| `reminder_absence_periods` | периоды отсутствий сотрудников (`date_from`, `date_to`, логин) |

## Поток рассылки

1. Команда запускается по расписанию и вызывает `DailyPollService`.
2. Для каждого активного канала проверяется **нужно ли отправлять сегодня** (`PollChannel::shouldSendPollOn`, см. ниже).
3. Утренний опрос: текст опроса + случайный факт (без неиспользованных фактов — отправляется без факта). Создаётся `daily_poll_session`, фиксируется `morning_message_id`.
4. Напоминания: из поля «Теги команд» строится состав (`teams.members` для тегов команд, `users.info` для логинов пользователей), вычитаются исключения канала и периоды отсутствий; неответившие (по сообщениям треда) тегаются в тред с mentions-пейлоадом и «пересылкой» (`[ ](permalink)`).
5. Идемпотентность: флаги сессии защищают от повторной отправки при повторных запусках.

## Логика дней отправки

Приоритет правил (в день проверки учитывается таймзона бота):

1. В `poll_schedule_exceptions` на дату есть запись с `send_polls = false` → **никому не отправлять**.
2. У канала выбраны `poll_days` → отправлять **только в выбранные дни недели** (даже в выходной).
3. Иначе (по умолчанию) → только в будние дни; в выходной — только при записи с `send_polls = true`.

Для напоминаний действует то же правило: напоминание уходит только в день, когда каналу отправлялся опрос.

## Rocket.Chat-клиент

`RocketChatClient` — один Guzzle-клиент с `base_uri .../api/v1/`, логин по паролю либо Personal Access Token (`X-User-Id` / `X-Auth-Token`). Используемые endpoint'ы: `channels.list`, `groups.list`, `im.list`, `chat.getThreadMessages`, `chat.sendMessage` (совместимый путь для RC 7.4+), `users.info`, `teams.members`, `channels.history` / `groups.history` / `im.history`. Ошибки API — `RocketChatException` (в командах логируются и не прерывают остальные каналы).

## Админка

- Страницы: каналы, факты, дни исключений, периоды отсутствий; всё под `middleware('admin.auth')`.
- На странице каналов список комнат синхронизируется с сервером Rocket.Chat при каждом открытии (`updateOrCreate` по `rocket_room_id`), форма сохраняет флаги и текстовые поля по каналу.