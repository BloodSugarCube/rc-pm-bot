<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyPollSession extends Model
{
    protected $fillable = [
        'poll_channel_id',
        'poll_date',
        'rocket_room_id',
        'morning_message_id',
        'day_message_id',
        'fact_id',
        'morning_reminder_sent',
        'day_poll_sent',
        'day_reminder_sent',
    ];

    protected function casts(): array
    {
        return [
            'poll_date' => 'date',
            'morning_reminder_sent' => 'boolean',
            'day_poll_sent' => 'boolean',
            'day_reminder_sent' => 'boolean',
        ];
    }

    public function pollChannel(): BelongsTo
    {
        return $this->belongsTo(PollChannel::class);
    }

    public function fact(): BelongsTo
    {
        return $this->belongsTo(Fact::class);
    }
}
