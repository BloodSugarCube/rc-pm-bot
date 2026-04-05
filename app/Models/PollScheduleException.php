<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class PollScheduleException extends Model
{
    protected $table = 'poll_schedule_exceptions';

    protected $fillable = [
        'exception_date',
        'send_polls',
    ];

    protected function casts(): array
    {
        return [
            'exception_date' => 'date',
            'send_polls' => 'boolean',
        ];
    }

    /**
     * Разрешена ли рассылка опросов/экспорта в эту дату (таймзона бота).
     * Запись с send_polls=false — не отправлять в этот день.
     * Запись с send_polls=true в выходной — отправлять, несмотря на выходной.
     */
    public static function isSendingAllowed(Carbon $now, string $timezone): bool
    {
        $local = $now->copy()->timezone($timezone)->startOfDay();
        $dateStr = $local->toDateString();
        $ex = static::query()->where('exception_date', $dateStr)->first();
        if ($ex !== null && ! $ex->send_polls) {
            return false;
        }
        if ($local->isWeekday()) {
            return true;
        }

        return $ex !== null && $ex->send_polls;
    }
}
