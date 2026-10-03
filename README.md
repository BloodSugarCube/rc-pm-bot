# RC PM Bot

Бот ежедневных опросов для **Rocket.Chat** на **Laravel** (PHP 8.3).

Утром бот публикует в выбранных комнатах вопрос «Что в работе?» со случайным фактом, в течение дня следит за тредом и тегает тех, кто не ответил, вечером выгружает сообщения активных комнат. Управление — через админ-панель `/admin`.

## Возможности

- **Утренний опрос** (`bot:morning-poll`) — сообщение в канал + случайный факт, не использованный в текущем году. Если фактов нет — опрос всё равно отправляется, просто без факта.
- **Утреннее напоминание** (`bot:morning-reminder`) — в треде утреннего опроса тегаются те, кто ещё не ответил.
- **Дневной опрос** (`bot:day-poll`) — повторный вопрос в том же треде.
- **Дневное напоминание** (`bot:day-reminder`) — теги неответивших после дневного опроса.
- **Экспорт** (`bot:export-messages`) — выгрузка всех сообщений активных комнат в JSON (`storage/app/exports`).
- **Админ-панель** `/admin`:
  - **Каналы** — включение опроса / дневного опроса для комнат, «Теги команд» (теги Rocket.Chat Teams **и** логины отдельных пользователей, например `@developers, @aleksandrbelyaev`), дни отправки (пн–вс; пусто — будние дни), «Не тегать в напоминаниях», «Периоды отсутствий»;
  - **Факты** — добавление/отключение, защита от повторного использования в течение года;
  - **Дни исключений** — запрет рассылки на дату или разрешение отправки в выходной;
  - **Периоды отсутствий** — даты отпусков сотрудников (не тегаются в напоминаниях).

## Архитектура

Кратко — в [architecture.md](architecture.md).

## Требования

- PHP >= 8.3, Composer
- Node.js + npm (сборка ассетов)
- MySQL 8+ (или SQLite)
- Rocket.Chat с учётной записью бота (пароль либо Personal Access Token)

## Установка

```bash
composer install
cp .env.example .env          # задать APP_KEY, DB_*, ROCKETCHAT_*, BOT_ADMIN_*
php artisan key:generate
php artisan migrate
npm install && npm run build
```

### Переменные окружения (основные)

| Переменная | Назначение |
| --- | --- |
| `ROCKETCHAT_URL` | адрес сервера Rocket.Chat |
| `ROCKETCHAT_BOT_USERNAME` / `ROCKETCHAT_BOT_PASSWORD` | учётка бота |
| `ROCKETCHAT_USER_ID` / `ROCKETCHAT_AUTH_TOKEN` | альтернатива паролю — Personal Access Token |
| `BOT_ADMIN_USERNAME` / `BOT_ADMIN_PASSWORD` | доступ к `/admin` |
| `BOT_TIMEZONE` | таймзона рассылок (по умолчанию `Europe/Moscow`) |
| `BOT_MORNING_POLL_TEXT` / `BOT_DAY_POLL_TEXT` | тексты опросов |
| `BOT_SCHEDULE_*` | время запуска задач (HH:MM) |

Полную логику разрешённых/запрещённых дней см. в [architecture.md](architecture.md).

## Запуск

Задачи расписываются в `bootstrap/app.php` (время — в `config/bot.php`, по умолчанию 07:30 / 09:30 / 12:30 / 13:30 / 19:00). В cron достаточно стандартного планировщика Laravel:

```cron
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

Обновление на сервере — `./update.sh`.

## Команды artisan

```bash
php artisan bot:morning-poll       # утренний опрос
php artisan bot:morning-reminder   # утреннее напоминание
php artisan bot:day-poll           # дневной опрос
php artisan bot:day-reminder       # дневное напоминание
php artisan bot:export-messages    # экспорт сообщений активных комнат
```