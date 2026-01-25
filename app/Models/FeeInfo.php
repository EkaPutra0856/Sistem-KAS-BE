<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeeInfo extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'amount_per_week',
        'badge_1',
        'badge_2',
        'badge_3',
        'updated_by',
    ];

    protected $casts = [
        'amount_per_week' => 'integer',
    ];

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
