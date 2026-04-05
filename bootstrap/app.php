<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin.auth' => \App\Http\Middleware\EnsureAdminAuthenticated::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule): void {
        $tz = (string) config('bot.timezone', 'Europe/Moscow');
        $sch = config('bot.schedule', []);
        $schedule->command('bot:morning-poll')
            ->dailyAt((string) ($sch['morning_poll_at'] ?? '07:30'))
            ->timezone($tz);
        $schedule->command('bot:morning-reminder')
            ->dailyAt((string) ($sch['morning_reminder_at'] ?? '09:30'))
            ->timezone($tz);
        $schedule->command('bot:day-poll')
            ->dailyAt((string) ($sch['day_poll_at'] ?? '12:30'))
            ->timezone($tz);
        $schedule->command('bot:export-messages')
            ->dailyAt((string) ($sch['export_at'] ?? '19:00'))
            ->timezone($tz);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
