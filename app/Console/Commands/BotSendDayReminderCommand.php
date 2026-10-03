<?php

namespace App\Console\Commands;

use App\Services\DailyPollService;
use Illuminate\Console\Command;

class BotSendDayReminderCommand extends Command
{
    protected $signature = 'bot:day-reminder';

    protected $description = 'Напоминание в треде дневного опроса с пересылкой и тегами отсутствующих';

    public function handle(DailyPollService $polls): int
    {
        $tz = (string) config('bot.timezone', 'Europe/Moscow');
        $polls->runDayReminders($tz);
        $this->info('Day reminder job finished.');

        return self::SUCCESS;
    }
}
