# Aura — Producto Vivo (Read-Only)

**Fecha:** 2025-12-26
**Estado:** FASE 19 completada

---

## Principio Fundamental

**Aura no es una demo. Aura es un sistema de gestión clínica real.**

Si no hay datos reales, no se inventan.
Si hay pocos, se muestran tal cual.
Si hay muchos, se ordenan y paginan.

---

## Diferencia: Demo vs Uso Real

### ❌ Modo Demo (Rechazado)

Un producto en "modo demo" muestra:
- Datos ficticios precargados
- Ejemplos que simulan uso real
- Experiencia guiada con tooltips y ayudas
- Interfaces que "enseñan" cómo usar el sistema

**Problema:** Confunde a usuarios reales. No refleja el estado real del sistema.

---

### ✅ Modo Producto Vivo (Aura)

Un producto vivo muestra:
- **Datos reales exclusivamente**
- Estados vacíos cuando no hay información
- Experiencia directa sin simulaciones
- Interfaces que **reflejan** el sistema, no lo explican

**Ventaja:** Los usuarios confían en lo que ven. El sistema no miente.

---

## Filosofía de Estados Vacíos

### Regla de Oro

> Un estado vacío bien explicado vale más que 10 ejemplos ficticios.

---

### Anatomía de un Estado Vacío Correcto

1. **Mensaje claro y humano**
   - Explica qué no hay
   - No usa jerga técnica
   - Habla en presente

2. **Sin ejemplos ficticios**
   - No muestra datos inventados
   - No simula información

3. **Sin ruido visual**
   - Diseño limpio
   - Fondo neutro
   - Tipografía legible

---

### Ejemplos de Estados Vacíos en Aura

#### Paciente sin visitas clínicas
```
┌─────────────────────────────────────────────────┐
│ Historial de Visitas                            │
├─────────────────────────────────────────────────┤
│                                                 │
│  Este paciente aún no tiene visitas clínicas   │
│  registradas.                                   │
│                                                 │
└─────────────────────────────────────────────────┘
```

**Por qué funciona:**
- ✅ Mensaje directo
- ✅ No inventa datos
- ✅ Usuario entiende el estado real

---

#### Visita sin tratamientos
```
┌─────────────────────────────────────────────────┐
│ 15 dic 2024, 10:30 · Visita con Dra. Pérez  ▾  │
├─────────────────────────────────────────────────┤
│  Revisión sin tratamientos realizados          │
└─────────────────────────────────────────────────┘
```

**Por qué funciona:**
- ✅ Contexto claro (revisión)
- ✅ No confunde con error
- ✅ Estado válido en clínicas reales

---

#### Sin facturación
```
┌─────────────────────────────────────────────────┐
│ Timeline de Facturación                         │
├─────────────────────────────────────────────────┤
│                                                 │
│  No hay facturación asociada a este paciente.  │
│                                                 │
└─────────────────────────────────────────────────┘
```

**Por qué funciona:**
- ✅ Específico (asociada a ESTE paciente)
- ✅ No sugiere error del sistema
- ✅ Estado válido (paciente recién creado)

---

## Navegación Real

### Acceso Correcto al Workspace

El Workspace del Paciente **no es un landing page**. Es un **destino**.

```
/dashboard
   │
   └─→ Ver Pacientes
         │
         └─→ /patients
               │
               └─→ Click en paciente
                     │
                     └─→ /workspace/patients/{id}
```

---

### ❌ Anti-Patrones Rechazados

1. **Acceso directo por URL manual**
   - No escribir `/workspace/patients/5` a mano
   - El workspace se accede desde la lista de pacientes

2. **Workspace como home**
   - El workspace no es la primera pantalla
   - Es un contexto específico de trabajo

3. **Enlaces globales al workspace**
   - No hay "Ir al Workspace" en el menú principal
   - El workspace es paciente-específico

---

## Principios de Diseño

### 1. Reflejo Fiel

El Workspace muestra **exactamente** lo que hay en el sistema.

- 0 visitas → se ve "0 visitas"
- 1 visita → se ve 1 visita
- 50 visitas → se ven 8/página con paginación

**No hay margen de interpretación.**

---

### 2. Sin Ruido

El Workspace **no enseña**, **no explica**, **no guía**.

- Sin tooltips educativos
- Sin ejemplos de uso
- Sin hints de "cómo usar esto"

**El usuario ya sabe qué busca.**

---

### 3. Coherencia en Vacío

Un estado vacío es **tan importante** como uno lleno.

- Mismo diseño visual
- Misma jerarquía
- Misma claridad

**El vacío no es un error, es un estado válido.**

---

### 4. Paginación Preventiva

Incluso con pocos datos, la paginación está activa.

- 8 items/página en timelines
- Controles siempre visibles (aunque deshabilitados)
- Preparado para escalabilidad

**El diseño no cambia con el volumen.**

---

## Casos de Uso Reales

### Paciente Recién Creado

**Contexto:** Clínica crea ficha de paciente antes de primera visita.

**Workspace muestra:**
- Resumen: 0 facturas, 0 pagos, 0 € facturado
- Historial de Visitas: "Este paciente aún no tiene visitas clínicas registradas."
- Billing Timeline: "No hay facturación asociada a este paciente."

**Comportamiento correcto:** El usuario ve exactamente el estado del paciente (recién creado, sin actividad).

---

### Paciente con Revisiones Sin Tratamiento

**Contexto:** Paciente viene a revisiones periódicas sin necesidad de tratamientos.

**Workspace muestra:**
- Visitas con mensaje: "Revisión sin tratamientos realizados"
- Billing Timeline: Vacío (no hay facturación si no hay tratamientos)

**Comportamiento correcto:** No confunde "sin tratamiento" con "error". Es un estado clínico válido.

---

### Clínica Nueva (Sin Datos Históricos)

**Contexto:** Clínica adopta Aura sin migrar datos antiguos.

**Workspace muestra:**
- Todos los pacientes con estados vacíos inicialmente
- A medida que se registran visitas, se llenan los timelines

**Comportamiento correcto:** El sistema no inventa historial. Refleja la realidad de datos disponibles.

---

## Reglas de Implementación

### Estados Vacíos

1. **Mensaje obligatorio**
   - Nunca mostrar UI vacía sin explicación
   - Formato: `<div class="aura-empty-state"><p>Mensaje claro</p></div>`

2. **Sin ejemplos ficticios**
   - Eliminar todos los `<details class="aura-visit-example">`
   - No renderizar datos inventados

3. **Idioma humano**
   - "Este paciente aún no..." (claro)
   - "No existen registros en la tabla..." (técnico, evitar)

---

### Navegación

1. **Flujo obligatorio**
   - Dashboard → Pacientes → Workspace
   - No shortcuts, no accesos directos

2. **Workspace como destino**
   - No landing page
   - No home
   - Contexto específico de un paciente

3. **Enlaces explícitos**
   - Cada paciente en lista es clickeable
   - Lleva directamente a su workspace

---

### Datos Reales

1. **Solo base de datos**
   - Nunca renderizar datos hardcoded
   - Variables Blade siempre desde controller

2. **0 es válido**
   - Mostrar "0" cuando no hay datos
   - No ocultar métricas vacías

3. **Paginación siempre activa**
   - Incluso si hay 2 items de 8
   - Controles deshabilitados pero visibles

---

## Seeders y Desarrollo

### Uso Permitido

Los seeders (`ValidationSeeder`, `WorkspaceSeeder`) **solo son para desarrollo**.

**Casos válidos:**
- Tests locales
- Validación de escalabilidad
- Demos a stakeholders (entorno dev)

---

### Uso Prohibido

Los seeders **nunca deben activar una experiencia "demo" por defecto**.

**Ejemplos de mal uso:**
- Seeders que se ejecutan en producción
- UI que detecta "datos de ejemplo" y los muestra
- Flags `isDemoMode` en código

---

### Regla de Oro

> Si el seeder no corrió, el sistema debe funcionar perfectamente con estados vacíos.

---

## Diferenciación con Próximas Fases

### FASE 19 (Actual): Read-Only Vivo

**Qué hay:**
- Workspace de lectura
- Estados vacíos reales
- Navegación end-to-end
- Datos exclusivamente de BD

**Qué NO hay:**
- Escritura clínica
- Creación de visitas
- Edición de tratamientos
- Gestión de facturas

---

### Próximas Fases (No Ahora)

Después de FASE 19, podremos decidir:

1. **FASE 20+: Escritura Clínica**
   - Crear visitas desde UI
   - Registrar tratamientos
   - Emitir facturas

2. **O congelar Aura como MVP sólido**
   - Producto vivo de solo lectura
   - Datos ingresados via seeders/importación
   - Consulta y análisis exclusivamente

**Decisión pendiente de negocio.**

---

## Checklist de Cumplimiento

### ✅ FASE 19 Completada

- [x] Ejemplos visuales eliminados del Workspace
- [x] Estados vacíos claros y humanos implementados
- [x] Navegación end-to-end funcional (dashboard → patients → workspace)
- [x] Workspace como reflejo fiel (0 → se ve 0)
- [x] Sin datos ficticios renderizados
- [x] Seeders solo para desarrollo
- [x] Paginación preventiva activa

---

### 🚫 Prohibiciones Respetadas

- [x] NO se añadió escritura clínica
- [x] NO se crearon nuevas entidades
- [x] NO se modificó arquitectura CQRS/eventos
- [x] NO se añadieron botones de acción (escritura)
- [x] NO se introdujo lógica de dominio nueva

---

## Conclusión

**Aura es un producto vivo.**

- Muestra datos reales
- No inventa información
- Refleja fielmente el sistema
- Estados vacíos son estados válidos

**El Workspace es un espejo, no un tutorial.**

---

**Fin de PRODUCT_LIVE.md**
