<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FactUsage extends Model
{
    public $timestamps = false;

    protected $fillable = ['fact_id', 'usage_year', 'used_at'];

    protected function casts(): array
    {
        return [
            'used_at' => 'datetime',
        ];
    }

    public function fact(): BelongsTo
    {
        return $this->belongsTo(Fact::class);
    }
}
