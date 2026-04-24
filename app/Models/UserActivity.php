<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserActivity extends Model
{
    protected $table = 'user_activity';

    protected $fillable = [
        'user_id',
        'activity_type',
        'details',
        'timestamp'
    ];

    protected $casts = [
        'timestamp' => 'datetime'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
