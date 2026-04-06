<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// команда для запуска: php artisan schedule:run

Schedule::command('bot:send-export-messages')->dailyAt('07:59');
Schedule::command('bot:morning-poll')->dailyAt('08:00');

Schedule::command('bot:send-export-messages')->dailyAt('09:29');
Schedule::command('bot:morning-reminder')->dailyAt('09:30');

Schedule::command('bot:send-export-messages')->dailyAt('12:29');
Schedule::command('bot:day-poll')->dailyAt('12:30');

Schedule::command('bot:send-export-messages')->dailyAt('20:59');
Schedule::command('bot:morning-poll')->dailyAt('21:00');
Schedule::command('bot:send-export-messages')->dailyAt('21:01');
Schedule::command('bot:morning-reminder')->dailyAt('21:02');
Schedule::command('bot:send-export-messages')->dailyAt('21:03');
Schedule::command('bot:day-poll')->dailyAt('21:04');
