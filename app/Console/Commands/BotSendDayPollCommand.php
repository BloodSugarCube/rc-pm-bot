<?php

namespace App\Console\Commands;

use App\Services\DailyPollService;
use Illuminate\Console\Command;

class BotSendDayPollCommand extends Command
{
    protected $signature = 'bot:day-poll';

    protected $description = 'Дневной опрос в том же треде (без пересылки утреннего сообщения)';

    public function handle(DailyPollService $polls): int
    {
        $tz = (string) config('bot.timezone', 'Europe/Moscow');
        $polls->runDayPolls($tz);
        $this->info('Day poll job finished.');

        return self::SUCCESS;
    }
}
