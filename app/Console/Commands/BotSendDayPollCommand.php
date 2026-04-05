<?php

namespace App\Console\Commands;

use App\Models\PollScheduleException;
use App\Services\DailyPollService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class BotSendDayPollCommand extends Command
{
    protected $signature = 'bot:day-poll';

    protected $description = 'Дневной опрос в том же треде (теги команд + текст)';

    public function handle(DailyPollService $polls): int
    {
        $tz = (string) config('bot.timezone', 'Europe/Moscow');
        if (! PollScheduleException::isSendingAllowed(Carbon::now($tz), $tz)) {
            $this->info('Пропуск: выходной или день без рассылки (см. админку «Дни исключений»).');

            return self::SUCCESS;
        }

        $polls->runDayPolls($tz);
        $this->info('Day poll job finished.');

        return self::SUCCESS;
    }
}
