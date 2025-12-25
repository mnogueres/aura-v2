{{-- Patient Identity Block (Read-only) --}}
<div class="aura-patient-identity">
    <div class="aura-patient-header">
        <h1 class="aura-patient-name">{{ $patient->first_name }} {{ $patient->last_name }}</h1>
        <span class="aura-patient-id">PC-{{ str_pad($patient->clinic_id, 3, '0', STR_PAD_LEFT) }}-{{ str_pad($patient->id, 2, '0', STR_PAD_LEFT) }}</span>
    </div>

    <div class="aura-patient-contacts">
        @if($patient->phone)
            <div class="aura-contact-item">
                <svg class="aura-contact-icon" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                </svg>
                <span>{{ $patient->phone }}</span>
            </div>
        @endif

        @if($patient->email)
            <div class="aura-contact-item">
                <svg class="aura-contact-icon" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
                <span>{{ $patient->email }}</span>
            </div>
        @endif
    </div>
</div>
