<?php

namespace App\Models;

use App\Models\Scopes\ClinicScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * Professional - Write model for clinical professionals.
 *
 * Represents a clinical professional (dentist, hygienist, etc.) in a clinic.
 * A professional is independent of system users.
 * user_id is optional and only for future association.
 *
 * Part of the write side (domain model).
 */
class Professional extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'clinic_id',
        'name',
        'role',
        'active',
        'user_id',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new ClinicScope);
    }

    /**
     * Get the route key name for Laravel route model binding.
     * Required for UUID primary keys.
     */
    public function getRouteKeyName(): string
    {
        return 'id';
    }

    public function clinic()
    {
        return $this->belongsTo(Clinic::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function visits()
    {
        return $this->hasMany(Visit::class);
    }
}
