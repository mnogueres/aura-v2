{{-- FASE 20.3: Partial para lista de tratamientos (HTMX swap target) --}}
@if($clinicalVisit && $clinicalVisit->treatments->isNotEmpty())
    <h3 class="aura-treatments-title">Tratamientos realizados:</h3>
    <ul class="aura-treatments-list">
        @foreach($clinicalVisit->treatments as $treatment)
            <li class="aura-treatment-item">
                {{-- Formato denso en una línea: Tipo (Pieza X) — notas · importe --}}
                <span class="aura-treatment-type">{{ $treatment->type }}</span>
                @if($treatment->tooth)
                    <span class="aura-treatment-tooth">(Pieza {{ $treatment->tooth }})</span>
                @endif
                @if($treatment->notes)
                    <span class="aura-treatment-notes" style="width: auto; margin: 0; padding: 0; display: inline;"> — {{ $treatment->notes }}</span>
                @endif
                @if($treatment->amount)
                    <span class="aura-treatment-amount"> · {{ number_format($treatment->amount, 2) }} €</span>
                @endif
            </li>
        @endforeach
    </ul>
@else
    <p class="aura-no-treatments">Revisión sin tratamientos realizados</p>
@endif
