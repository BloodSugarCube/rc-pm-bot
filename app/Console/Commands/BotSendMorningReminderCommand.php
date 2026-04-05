<?php

namespace App\Console\Commands;

use App\Models\PollScheduleException;
use App\Services\DailyPollService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class BotSendMorningReminderCommand extends Command
{
    protected $signature = 'bot:morning-reminder';

    protected $description = 'Повтор в треде утреннего опроса с пересылкой и тегами отсутствующих';

    public function handle(DailyPollService $polls): int
    {
        $tz = (string) config('bot.timezone', 'Europe/Moscow');
        if (! PollScheduleException::isSendingAllowed(Carbon::now($tz), $tz)) {
            $this->info('Пропуск: выходной или день без рассылки (см. админку «Дни исключений»).');

            return self::SUCCESS;
        }

        $polls->runMorningReminders($tz);
        $this->info('Morning reminder job finished.');

        return self::SUCCESS;
    }
}
