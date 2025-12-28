{{-- FASE 21.0/21.1: Modal to create new professional --}}

<div
    id="new-professional-modal"
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
            Nuevo profesional
        </h2>

        <form
            hx-post="{{ route('workspace.professionals.store') }}"
            hx-target="#professionals-list"
            hx-swap="innerHTML"
            hx-on::after-request="if(event.detail.successful) { document.getElementById('new-professional-modal').style.display = 'none'; this.reset(); }"
        >
            @csrf

            <div style="margin-bottom: 1rem;">
                <label for="name" style="display: block; margin-bottom: 0.5rem; font-weight: 500; font-size: 0.875rem; color: #374151;">
                    Nombre completo <span style="color: #dc2626;">*</span>
                </label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    required
                    autofocus
                    placeholder="Dr. Juan Pérez"
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
                <label for="role" style="display: block; margin-bottom: 0.5rem; font-weight: 500; font-size: 0.875rem; color: #374151;">
                    Rol <span style="color: #dc2626;">*</span>
                </label>
                <select
                    id="role"
                    name="role"
                    required
                    style="
                        width: 100%;
                        padding: 0.625rem 0.875rem;
                        border: 1px solid #d1d5db;
                        border-radius: 6px;
                        font-size: 0.875rem;
                        background: white;
                        cursor: pointer;
                        transition: border-color 0.2s, box-shadow 0.2s;
                    "
                    onfocus="this.style.borderColor='#0ea5e9'; this.style.boxShadow='0 0 0 3px rgba(14, 165, 233, 0.1)';"
                    onblur="this.style.borderColor='#d1d5db'; this.style.boxShadow='none';"
                >
                    <option value="dentist">Odontólogo/a</option>
                    <option value="hygienist">Higienista</option>
                    <option value="assistant">Asistente</option>
                    <option value="other">Otro</option>
                </select>
            </div>

            <div style="display: flex; gap: 0.75rem; justify-content: flex-end;">
                <button
                    type="button"
                    onclick="document.getElementById('new-professional-modal').style.display = 'none'"
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
                    Crear profesional
                </button>
            </div>
        </form>
    </div>
</div>
