<?php

namespace App\Console\Commands;

use App\Services\DailyPollService;
use Illuminate\Console\Command;

class BotSendMorningReminderCommand extends Command
{
    protected $signature = 'bot:morning-reminder';

    protected $description = 'Повтор в треде утреннего опроса с пересылкой и тегами отсутствующих';

    public function handle(DailyPollService $polls): int
    {
        $tz = (string) config('bot.timezone', 'Europe/Moscow');
        $polls->runMorningReminders($tz);
        $this->info('Morning reminder job finished.');

        return self::SUCCESS;
    }
}
