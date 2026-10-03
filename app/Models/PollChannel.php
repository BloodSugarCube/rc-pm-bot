<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PollChannel extends Model
{
    protected $fillable = [
        'rocket_room_id',
        'name',
        'room_type',
        'is_poll_active',
        'is_day_poll_active',
        'team_tags',
        'reminder_exclude_tags',
        'poll_days',
        'last_exported_at',
    ];

    protected function casts(): array
    {
        return [
            'is_poll_active' => 'boolean',
            'is_day_poll_active' => 'boolean',
            'last_exported_at' => 'datetime',
            'poll_days' => 'array',
        ];
    }

    public function dailyPollSessions(): HasMany
    {
        return $this->hasMany(DailyPollSession::class);
    }

    public function scopePollActive($query)
    {
        return $query->where('is_poll_active', true);
    }

    public function scopeDayPollActive($query)
    {
        return $query->where('is_day_poll_active', true);
    }

    /**
     * Рабочие дни канала для опросов/напоминаний: ISO-номера 1..7 (пн..вс).
     * Пусто → по умолчанию будние дни (учитывая глобальные дни исключений).
     *
     * @return list<int>
     */
    public function pollDays(): array
    {
        $days = $this->poll_days ?? [];

        return array_values(array_unique(array_map('intval', array_filter(
            is_array($days) ? $days : [],
            fn ($d): bool => is_numeric($d) && (int) $d >= 1 && (int) $d <= 7,
        ))));
    }

    /**
     * Нужно ли отправлять опрос/напоминания каналу в эту дату.
     * Приоритет: явный запрет в «Днях исключений» → выбранные дни недели → по умолчанию будние дни.
     */
    public function shouldSendPollOn(Carbon $now, string $timezone): bool
    {
        $local = $now->copy()->timezone($timezone)->startOfDay();
        $dateStr = $local->toDateString();

        $ex = PollScheduleException::query()->where('exception_date', $dateStr)->first();
        if ($ex !== null && ! $ex->send_polls) {
            return false; // явный запрет на дату действует на все каналы
        }

        $selected = $this->pollDays();
        if ($selected !== []) {
            return in_array($local->dayOfWeekIso, $selected, true);
        }

        return $local->isWeekday() || ($ex !== null && $ex->send_polls);
    }
}
