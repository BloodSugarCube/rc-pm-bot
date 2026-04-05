<?php

namespace App\Console\Commands;

use App\Models\PollScheduleException;
use App\Services\DailyPollService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class BotSendMorningPollCommand extends Command
{
    protected $signature = 'bot:morning-poll';

    protected $description = 'Отправить утренний опрос (@all + факт) в активных каналах';

    public function handle(DailyPollService $polls): int
    {
        $tz = (string) config('bot.timezone', 'Europe/Moscow');
        if (! PollScheduleException::isSendingAllowed(Carbon::now($tz), $tz)) {
            $this->info('Пропуск: выходной или день без рассылки (см. админку «Дни исключений»).');

            return self::SUCCESS;
        }

        $polls->runMorningPolls($tz);
        $this->info('Morning poll job finished.');

        return self::SUCCESS;
    }
}
