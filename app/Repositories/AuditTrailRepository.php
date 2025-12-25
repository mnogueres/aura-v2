<?php

namespace App\Repositories;

use App\Models\AuditTrail;
use Illuminate\Support\Collection;

class AuditTrailRepository
{
    /**
     * Get audit trail for a specific clinic.
     *
     * Returns events in reverse chronological order (newest first).
     *
     * @param int $clinicId
     * @param int $limit
     * @return Collection<AuditTrail>
     */
    public function getForClinic(int $clinicId, int $limit = 100): Collection
    {
        return AuditTrail::where('clinic_id', $clinicId)
            ->orderBy('occurred_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get audit trail by severity level.
     *
     * Returns events in reverse chronological order (newest first).
     *
     * @param string $severity
     * @param int|null $clinicId
     * @param int $limit
     * @return Collection<AuditTrail>
     */
    public function getBySeverity(string $severity, ?int $clinicId = null, int $limit = 100): Collection
    {
        $query = AuditTrail::where('severity', $severity);

        if ($clinicId !== null) {
            $query->where('clinic_id', $clinicId);
        }

        return $query->orderBy('occurred_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get recent audit trail entries.
     *
     * Returns events in reverse chronological order (newest first).
     *
     * @param int|null $clinicId
     * @param int $limit
     * @return Collection<AuditTrail>
     */
    public function getRecent(?int $clinicId = null, int $limit = 50): Collection
    {
        $query = AuditTrail::query();

        if ($clinicId !== null) {
            $query->where('clinic_id', $clinicId);
        }

        return $query->orderBy('occurred_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get audit trail by category.
     *
     * Returns events in reverse chronological order (newest first).
     *
     * @param string $category
     * @param int|null $clinicId
     * @param int $limit
     * @return Collection<AuditTrail>
     */
    public function getByCategory(string $category, ?int $clinicId = null, int $limit = 100): Collection
    {
        $query = AuditTrail::where('category', $category);

        if ($clinicId !== null) {
            $query->where('clinic_id', $clinicId);
        }

        return $query->orderBy('occurred_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
