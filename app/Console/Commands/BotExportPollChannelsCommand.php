<?php

namespace App\Console\Commands;

use App\Models\PollScheduleException;
use App\Services\DailyPollService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class BotExportPollChannelsCommand extends Command
{
    protected $signature = 'bot:export-messages';

    protected $description = 'Выгрузить каналы и сообщения по активным для опроса комнатам';

    public function handle(DailyPollService $polls): int
    {
        $tz = (string) config('bot.timezone', 'Europe/Moscow');
        if (! PollScheduleException::isSendingAllowed(Carbon::now($tz), $tz)) {
            $this->info('Пропуск: выходной или день без рассылки (экспорт отключён для этой даты).');

            return self::SUCCESS;
        }

        $path = $polls->exportActiveChannelMessages();
        $this->info('Export written to: '.$path);

        return self::SUCCESS;
    }
}
