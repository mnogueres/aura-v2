<?php

namespace App\Observers;

use App\Events\Clinical\ProfessionalCreated;
use App\Events\Clinical\ProfessionalUpdated;
use App\Events\Clinical\ProfessionalDeactivated;
use App\Models\Professional;
use App\Services\EventService;
use Illuminate\Support\Str;

/**
 * ProfessionalObserver - Auto-emit domain events for Professional model.
 *
 * This observer ensures that domain events are emitted even when
 * Professional instances are created via factory (for tests) or
 * directly via Eloquent (legacy code).
 *
 * In production, prefer using ClinicalProfessionalService which
 * explicitly emits events.
 */
class ProfessionalObserver
{
    public function __construct(
        private readonly EventService $eventService
    ) {
    }

    /**
     * Handle the Professional "created" event.
     */
    public function created(Professional $professional): void
    {
        $event = new ProfessionalCreated(
            clinic_id: $professional->clinic_id,
            professional_id: $professional->id,
            name: $professional->name,
            role: $professional->role,
            active: $professional->active,
            professional_user_id: $professional->user_id,
            request_id: (string) Str::uuid(),
            user_id: null
        );

        // Emit event to outbox
        $this->eventService->emit($event);
    }

    /**
     * Handle the Professional "updated" event.
     */
    public function updated(Professional $professional): void
    {
        // Check if deactivated
        if ($professional->isDirty('active') && !$professional->active) {
            $event = new ProfessionalDeactivated(
                clinic_id: $professional->clinic_id,
                professional_id: $professional->id,
                request_id: (string) Str::uuid(),
                user_id: null
            );
        } else {
            $event = new ProfessionalUpdated(
                clinic_id: $professional->clinic_id,
                professional_id: $professional->id,
                name: $professional->name,
                role: $professional->role,
                active: $professional->active,
                professional_user_id: $professional->user_id,
                request_id: (string) Str::uuid(),
                user_id: null
            );
        }

        // Emit event to outbox
        $this->eventService->emit($event);
    }
}
