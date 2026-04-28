<?php

namespace App\Models;

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
        'last_exported_at',
    ];

    protected function casts(): array
    {
        return [
            'is_poll_active' => 'boolean',
            'is_day_poll_active' => 'boolean',
            'last_exported_at' => 'datetime',
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
}
