<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class IdempotencyKey extends Model
{
    protected $fillable = [
        'key',
        'user_id',
        'clinic_id',
        'endpoint',
        'method',
        'request_hash',
        'response_status',
        'response_body',
        'expires_at',
    ];

    protected $casts = [
        'response_body' => 'array',
        'expires_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function scopeNotExpired(Builder $query): Builder
    {
        return $query->where('expires_at', '>', now());
    }

    public function scopeForContext(Builder $query, ?int $userId, ?int $clinicId, string $endpoint, string $method): Builder
    {
        return $query
            ->where('user_id', $userId)
            ->where('clinic_id', $clinicId)
            ->where('endpoint', $endpoint)
            ->where('method', $method);
    }

    public static function findByKey(string $key): ?self
    {
        return static::where('key', $key)->notExpired()->first();
    }
}
