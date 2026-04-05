<?php

return [

    'timezone' => env('BOT_TIMEZONE', 'Europe/Moscow'),

    'rocketchat' => [
        'url' => rtrim((string) env('ROCKETCHAT_URL', ''), '/'),
        'username' => env('ROCKETCHAT_BOT_USERNAME', 'bot_ai'),
        'password' => env('ROCKETCHAT_BOT_PASSWORD', ''),
        'auth_user_id' => env('ROCKETCHAT_USER_ID'),
        'auth_token' => env('ROCKETCHAT_AUTH_TOKEN'),
    ],

    'admin' => [
        'username' => env('BOT_ADMIN_USERNAME', 'admin'),
        'password' => env('BOT_ADMIN_PASSWORD', ''),
    ],

    'poll' => [
        'morning_text' => env('BOT_MORNING_POLL_TEXT', '@all Что в работе?'),
        'day_text' => env('BOT_DAY_POLL_TEXT', 'Что в работе?'),
        'export_path' => storage_path('app/exports'),
    ],

    /*
     * Расписание artisan-команд (Europe/Moscow по умолчанию, см. timezone выше).
     * Формат времени HH:MM для Schedule::at().
     */
    'schedule' => [
        'morning_poll_at' => env('BOT_SCHEDULE_MORNING_POLL_AT', '07:30'),
        'morning_reminder_at' => env('BOT_SCHEDULE_MORNING_REMINDER_AT', '09:30'),
        'day_poll_at' => env('BOT_SCHEDULE_DAY_POLL_AT', '12:30'),
        'export_at' => env('BOT_SCHEDULE_EXPORT_AT', '19:00'),
    ],

];
