<?php

namespace App\Console\Commands;

use App\Services\DailyPollService;
use Illuminate\Console\Command;

class BotSendMorningPollCommand extends Command
{
    protected $signature = 'bot:morning-poll';

    protected $description = 'Отправить утренний опрос (@all + факт) в активных каналах';

    public function handle(DailyPollService $polls): int
    {
        $tz = (string) config('bot.timezone', 'Europe/Moscow');
        $polls->runMorningPolls($tz);
        $this->info('Morning poll job finished.');

        return self::SUCCESS;
    }
}
