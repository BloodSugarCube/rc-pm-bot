<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ReminderAbsencePeriod extends Model
{
    protected $fillable = [
        'date_from',
        'date_to',
        'employee_tag',
        'username_normalized',
    ];

    protected function casts(): array
    {
        return [
            'date_from' => 'date',
            'date_to' => 'date',
        ];
    }

    public static function normalizeUsername(string $tag): string
    {
        return Str::lower(ltrim(trim($tag), '@'));
    }

    /**
     * @return list<string> lowercase usernames, без дубликатов
     */
    public static function usernamesAbsentOnDate(string $yyyyMmDd): array
    {
        return static::query()
            ->where('date_from', '<=', $yyyyMmDd)
            ->where('date_to', '>=', $yyyyMmDd)
            ->pluck('username_normalized')
            ->map(fn (string $u): string => Str::lower($u))
            ->unique()
            ->values()
            ->all();
    }
}
