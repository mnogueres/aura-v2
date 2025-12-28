{{-- FASE 21.3: Modal to create new patient (Aura canonical style) --}}

<div
    id="new-patient-modal"
    @keydown.escape.window="if($el.style.display === 'flex') $el.style.display = 'none'"
    @click.self="$el.style.display = 'none'"
    style="
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        z-index: 1000;
        align-items: center;
        justify-content: center;
    ">
    <div style="
        background: white;
        padding: 2rem;
        border-radius: 8px;
        max-width: 500px;
        width: 90%;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    ">
        <h2 style="margin: 0 0 1.5rem 0; font-size: 1.25rem; font-weight: 600; color: #1e293b;">
            Nuevo paciente
        </h2>

        <form
            hx-post="{{ route('patients.store') }}"
            hx-target="#patients-content"
            hx-swap="outerHTML"
            hx-on::after-request="if(event.detail.successful) { document.getElementById('new-patient-modal').style.display = 'none'; this.reset(); }"
        >
            @csrf

            <div style="margin-bottom: 1rem;">
                <label for="first_name" style="display: block; margin-bottom: 0.5rem; font-weight: 500; font-size: 0.875rem; color: #374151;">
                    Nombre <span style="color: #dc2626;">*</span>
                </label>
                <input
                    type="text"
                    id="first_name"
                    name="first_name"
                    required
                    autofocus
                    placeholder="Juan"
                    style="
                        width: 100%;
                        padding: 0.625rem 0.875rem;
                        border: 1px solid #d1d5db;
                        border-radius: 6px;
                        font-size: 0.875rem;
                        transition: border-color 0.2s, box-shadow 0.2s;
                    "
                    onfocus="this.style.borderColor='#0ea5e9'; this.style.boxShadow='0 0 0 3px rgba(14, 165, 233, 0.1)';"
                    onblur="this.style.borderColor='#d1d5db'; this.style.boxShadow='none';"
                >
            </div>

            <div style="margin-bottom: 1rem;">
                <label for="last_name" style="display: block; margin-bottom: 0.5rem; font-weight: 500; font-size: 0.875rem; color: #374151;">
                    Apellidos <span style="color: #dc2626;">*</span>
                </label>
                <input
                    type="text"
                    id="last_name"
                    name="last_name"
                    required
                    placeholder="García López"
                    style="
                        width: 100%;
                        padding: 0.625rem 0.875rem;
                        border: 1px solid #d1d5db;
                        border-radius: 6px;
                        font-size: 0.875rem;
                        transition: border-color 0.2s, box-shadow 0.2s;
                    "
                    onfocus="this.style.borderColor='#0ea5e9'; this.style.boxShadow='0 0 0 3px rgba(14, 165, 233, 0.1)';"
                    onblur="this.style.borderColor='#d1d5db'; this.style.boxShadow='none';"
                >
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label for="dni" style="display: block; margin-bottom: 0.5rem; font-weight: 500; font-size: 0.875rem; color: #374151;">
                    DNI/NIE <span style="color: #dc2626;">*</span>
                </label>
                <input
                    type="text"
                    id="dni"
                    name="dni"
                    required
                    placeholder="12345678A"
                    style="
                        width: 100%;
                        padding: 0.625rem 0.875rem;
                        border: 1px solid #d1d5db;
                        border-radius: 6px;
                        font-size: 0.875rem;
                        transition: border-color 0.2s, box-shadow 0.2s;
                    "
                    onfocus="this.style.borderColor='#0ea5e9'; this.style.boxShadow='0 0 0 3px rgba(14, 165, 233, 0.1)';"
                    onblur="this.style.borderColor='#d1d5db'; this.style.boxShadow='none';"
                >
            </div>

            <div style="display: flex; gap: 0.75rem; justify-content: flex-end;">
                <button
                    type="button"
                    onclick="document.getElementById('new-patient-modal').style.display = 'none'"
                    style="
                        padding: 0.625rem 1rem;
                        border: 1px solid #d1d5db;
                        background: white;
                        color: #374151;
                        border-radius: 6px;
                        font-size: 0.875rem;
                        font-weight: 500;
                        cursor: pointer;
                        transition: background 0.2s, border-color 0.2s;
                    "
                    onmouseover="this.style.background='#f9fafb'; this.style.borderColor='#9ca3af';"
                    onmouseout="this.style.background='white'; this.style.borderColor='#d1d5db';"
                >
                    Cancelar
                </button>
                <button
                    type="submit"
                    style="
                        padding: 0.625rem 1rem;
                        border: none;
                        background: #0ea5e9;
                        color: white;
                        border-radius: 6px;
                        font-size: 0.875rem;
                        font-weight: 500;
                        cursor: pointer;
                        transition: background 0.2s;
                    "
                    onmouseover="this.style.background='#0284c7';"
                    onmouseout="this.style.background='#0ea5e9';"
                >
                    Crear paciente
                </button>
            </div>
        </form>
    </div>
</div>
