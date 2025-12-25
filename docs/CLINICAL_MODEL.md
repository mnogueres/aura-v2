# Modelo Clínico de Aura

**Versión:** 1.0
**Fecha:** 2025-12-25
**Tipo:** Diseño conceptual (no implementación)

---

## A. Definición de conceptos

### ¿Qué es una Visita?

Una **Visita** es el encuentro físico entre un paciente y un profesional de la clínica en una fecha específica.

Es la unidad central de la experiencia clínica en Aura. Toda actividad clínica se organiza alrededor de visitas.

**Características clave:**
- Tiene una fecha y hora determinadas
- Tiene un profesional responsable
- Puede incluir uno o varios tratamientos realizados durante ese encuentro
- Puede tener un resumen o nota clínica general
- Puede generar facturación (o no, si es valoración/revisión)

**Lenguaje humano:**
- "El martes atendí a la paciente María"
- "En la visita del 15 de diciembre se hicieron dos empastes"
- "Fue una visita de revisión, sin tratamiento"

### ¿Qué NO es una Visita?

Una Visita **no es**:
- Una cita futura (eso es una agenda, no una visita realizada)
- Un tratamiento individual (una visita puede contener varios tratamientos)
- Un registro administrativo (una factura, un pago)
- Un evento técnico del sistema

**Distinción importante:**
- **Cita** = intención futura ("tengo cita el martes")
- **Visita** = hecho pasado o presente ("hoy vino el paciente")

### ¿Qué es un Tratamiento?

Un **Tratamiento** es una intervención clínica específica realizada durante una visita.

**Características clave:**
- Pertenece siempre a una visita (no existe tratamiento sin visita)
- Tiene un tipo clínico (empaste, limpieza, extracción, endodoncia, etc.)
- Puede estar asociado a una pieza dental específica (diente 16, 23, etc.)
- Puede tener duración estimada
- Puede tener un importe asociado (o ser gratuito)
- Puede tener notas clínicas específicas del procedimiento

**Lenguaje humano:**
- "Le hice un empaste en el 16"
- "Realizamos limpieza y pulido"
- "Se extrajo el 48 con sutura"

### Relación Visita ↔ Tratamiento

```
VISITA (1) ────── contiene ────── (0..N) TRATAMIENTOS

Ejemplos:
- Visita de revisión: 0 tratamientos
- Visita de limpieza: 1 tratamiento
- Visita compleja: 3 tratamientos (empaste + pulido + aplicación flúor)
```

**Regla fundamental:**
- Una visita puede existir sin tratamientos (valoración, revisión, consulta)
- Un tratamiento NO puede existir sin visita

**Desde la perspectiva del usuario:**
- El profesional ve la visita como el contexto temporal
- Los tratamientos son los detalles clínicos de esa visita
- La visita responde "¿cuándo vino?"
- Los tratamientos responden "¿qué se hizo?"

---

## B. Modelo conceptual (no técnico)

### Visita

**Campos conceptuales:**

| Campo | Descripción | Ejemplo |
|-------|-------------|---------|
| Fecha | Cuándo ocurrió la visita | 15 de diciembre de 2025 |
| Hora | Hora de inicio (opcional) | 10:30 |
| Tipo de visita | Clasificación general | Primera visita / Revisión / Tratamiento / Urgencia |
| Profesional | Quién atendió | Dr. García |
| Paciente | A quién se atendió | María López |
| Resumen | Nota general de la visita | "Paciente refiere dolor en molar inferior derecho" |
| Tiene tratamientos | Indicador | Sí / No |
| Duración total | Tiempo estimado o real | 45 minutos |
| Estado | Finalizada, En curso, Cancelada | Finalizada |

**Nota importante:**
No almacenamos "importe total de la visita" como campo. El importe se calcula sumando los tratamientos realizados.

### Tratamiento

**Campos conceptuales:**

| Campo | Descripción | Ejemplo |
|-------|-------------|---------|
| Tipo | Clasificación clínica | Empaste / Limpieza / Extracción |
| Diente | Pieza dental (si aplica) | 16, 23, 48 |
| Superficie | Cara del diente (si aplica) | Oclusal, Mesial, Distal |
| Duración | Tiempo estimado | 30 minutos |
| Notas clínicas | Detalle del procedimiento | "Composite fotopolimerizable clase II" |
| Material usado | Insumos principales (opcional) | "Composite 3M Z250" |
| Importe | Precio del tratamiento | 65,00 € |
| Estado | Realizado, Planificado, Cancelado | Realizado |

**Visibilidad:**
- El profesional ve todos los campos
- La auxiliar puede registrar pero no modificar notas clínicas
- El contable solo ve tipo, importe y fecha

---

## C. Timeline clínico (visión humana)

### ¿Qué SÍ aparece en el timeline del paciente?

El timeline clínico muestra **eventos significativos desde la perspectiva humana**:

**Visitas realizadas:**
- "15 dic 2025 - Visita con Dr. García"
  - Detalle: "Empaste en 16, Limpieza"

**Tratamientos relevantes:**
- Solo si se despliega el detalle de la visita
- Nunca como líneas separadas del timeline principal

**Información financiera relacionada:**
- "20 dic 2025 - Factura INV-023-25 emitida (150,00 €)"
- "22 dic 2025 - Pago recibido (150,00 €)"

**Hitos administrativos:**
- "10 dic 2025 - Paciente creado en el sistema"

### ¿Qué NO aparece nunca?

**Eventos técnicos del sistema:**
- ❌ "crm.patient.created"
- ❌ "billing.invoice.issued"
- ❌ "platform.idempotency.replayed"

**Ruido interno:**
- ❌ Cambios de estado técnico
- ❌ Logs de auditoría técnica
- ❌ Eventos de sincronización

**Fragmentación excesiva:**
- ❌ Un tratamiento como evento separado de su visita
- ❌ Cada pago parcial en línea separada (se agrupan)

### Ejemplos de líneas correctas

```
✅ 15 dic 2025, 10:30 - Visita con Dr. García
   Tratamientos: Empaste (16), Limpieza general
   Duración: 45 min

✅ 20 dic 2025 - Factura INV-023-25 emitida
   Importe: 150,00 €
   Estado: Emitida

✅ 10 dic 2025 - Paciente registrado
   Alta en el sistema
```

### Ejemplos de líneas incorrectas

```
❌ 15 dic 2025 - Evento: crm.visit.created
   (Demasiado técnico)

❌ 15 dic 2025, 10:30 - Empaste realizado
   (Sin contexto de visita)

❌ 20 dic 2025 - billing.invoice.issued
   (Lenguaje de sistema, no humano)

❌ 22 dic 2025 - platform.idempotency.replayed
   (Ruido técnico, invisible para usuario)
```

### Principios del timeline clínico

1. **Lenguaje natural:** "Visita con Dr. García", no "crm.visit.created"
2. **Agrupación lógica:** Los tratamientos viven dentro de la visita
3. **Detalle bajo demanda:** El timeline muestra el qué, el detalle muestra el cómo
4. **Relevancia clínica:** Solo lo que responde "¿qué pasó con este paciente?"

---

## D. Roles y expectativas

### Auxiliar (recepción/asistencia)

**Qué le aporta este modelo:**
- Ve cuándo vino el paciente por última vez
- Sabe qué tratamientos se hicieron
- Puede registrar nuevas visitas
- Puede asociar tratamientos a visitas
- Tiene visibilidad de facturación pendiente

**Qué NO debe ver:**
- Notas clínicas detalladas del profesional
- Eventos técnicos del sistema
- Detalles de auditoría técnica
- Logs de errores o problemas internos

**Experiencia esperada:**
- Timeline limpio y cronológico
- Visitas como bloques expandibles
- Resumen visual de actividad reciente

### Profesional clínico

**Qué le aporta este modelo:**
- Historial completo de visitas que realizó
- Detalle de todos los tratamientos por visita
- Contexto temporal de intervenciones
- Planificación de próximos tratamientos
- Notas clínicas privadas por visita

**Qué NO debe ver:**
- Eventos de plataforma (rate limiting, idempotency)
- Detalles financieros si no es su responsabilidad
- Logs técnicos

**Experiencia esperada:**
- Vista centrada en historial clínico
- Tratamientos agrupados por visita
- Acceso rápido a notas anteriores
- Búsqueda por tipo de tratamiento o diente

### Contable

**Qué le aporta este modelo:**
- Relación visita → tratamientos → importe
- Trazabilidad de facturación
- Verificación de servicios prestados vs facturados
- Control de pagos aplicados

**Qué NO debe ver:**
- Notas clínicas privadas del profesional
- Detalles técnicos de tratamientos
- Eventos del sistema
- Información médica protegida

**Experiencia esperada:**
- Timeline orientado a facturación
- Visitas con importe total calculado
- Estado de cobro por visita
- Filtrado por estado financiero

---

## E. Relación con eventos (interno)

### Los eventos existen

Aura utiliza una arquitectura event-driven:
- Los cambios en el dominio emiten eventos
- Los eventos alimentan proyecciones
- Las proyecciones construyen vistas de lectura

**Eventos relacionados con el modelo clínico** (futuros):
- `clinical.visit.created`
- `clinical.visit.completed`
- `clinical.treatment.performed`
- `clinical.visit.cancelled`

### Los eventos NO se muestran directamente

**Regla fundamental:**
El usuario nunca ve nombres técnicos de eventos.

**Por qué:**
- Los eventos son mecanismos internos de comunicación
- Su nomenclatura es técnica, no clínica
- Su granularidad es diferente a la visibilidad humana
- Un solo evento puede generar múltiples líneas de timeline, o viceversa

### Los eventos alimentan proyecciones clínicas

**Flujo interno (invisible para el usuario):**

```
[Acción del usuario]
      ↓
[Evento de dominio emitido]
      ↓
[Projector escucha el evento]
      ↓
[Proyección actualizada]
      ↓
[Vista de lectura se refresca]
      ↓
[Usuario ve información humanizada]
```

**Ejemplo concreto:**

1. Dr. García registra una visita con 2 tratamientos
2. Sistema emite: `clinical.visit.created`
3. Sistema emite: `clinical.treatment.performed` (x2)
4. Projector `PatientTimelineProjector` escucha estos eventos
5. Proyección `patient_timeline` se actualiza con una única línea:
   - "15 dic 2025 - Visita con Dr. García (2 tratamientos)"
6. Workspace muestra esta línea humanizada

**El usuario ve:** "Visita con Dr. García"
**El sistema procesó:** 3 eventos técnicos

### Separación de responsabilidades

**Capa de dominio (eventos):**
- Lenguaje técnico
- Granularidad fina
- Inmutabilidad
- Trazabilidad completa

**Capa de lectura (proyecciones):**
- Lenguaje humano
- Agrupación lógica
- Desnormalización
- Optimización para consulta

**Capa de presentación (Workspace):**
- Lenguaje clínico natural
- Jerarquía visual
- Detalle bajo demanda
- Sin términos técnicos

---

## F. Workspace actual y modelo clínico

### Estado actual del Workspace (FASE 15)

El Workspace ya implementa:
- `PatientSummary` (contadores, totales)
- `PatientTimeline` (eventos cronológicos)
- `BillingTimeline` (timeline financiero)
- `AuditTrail` (auditoría técnica)

**Qué falta:**
- Timeline clínico específico
- Visitas como entidad visible
- Tratamientos agrupados por visita

### Cómo encaja el modelo clínico

**PatientTimeline actual:**
Muestra eventos de CRM y Billing humanizados.

**PatientTimeline futuro (clínico):**
Mostrará visitas como eventos principales, con tratamientos anidados.

**Ejemplo de transición:**

**Antes (solo CRM/Billing):**
```
- 10 dic 2025: Paciente creado
- 20 dic 2025: Factura emitida (150€)
- 22 dic 2025: Pago recibido (150€)
```

**Después (con modelo clínico):**
```
- 10 dic 2025: Paciente registrado
- 15 dic 2025: Visita con Dr. García
  └─ Empaste (16), Limpieza general
- 20 dic 2025: Factura INV-023-25 emitida (150€)
- 22 dic 2025: Pago recibido (150€)
```

### Proyecciones necesarias (futuro)

**VisitTimeline:**
- Una línea por visita
- Profesional, fecha, resumen
- Contador de tratamientos
- Importe total calculado

**TreatmentDetails:**
- Detalle expandible bajo cada visita
- Listado de tratamientos con tipo, diente, importe
- Notas clínicas (si el rol lo permite)

---

## G. Validación conceptual

### Pregunta 1: ¿Qué es una visita?
✅ **Respuesta clara:** Encuentro físico paciente-profesional en fecha determinada

### Pregunta 2: ¿Qué es un tratamiento?
✅ **Respuesta clara:** Intervención clínica específica dentro de una visita

### Pregunta 3: ¿Qué ve el usuario en el Workspace?
✅ **Respuesta clara:** Timeline clínico con visitas humanizadas, tratamientos agrupados, sin eventos técnicos

### Pregunta 4: ¿Qué NO debe verse nunca?
✅ **Respuesta clara:** Eventos técnicos, logs, nombres de sistema

### Pregunta 5: ¿Cómo se relaciona con la arquitectura actual?
✅ **Respuesta clara:** Los eventos alimentan proyecciones clínicas que construyen el timeline humanizado

---

## H. Siguiente paso (no implementación)

Este documento define el **qué** del modelo clínico, no el **cómo**.

**Entregables de FASE 16:**
- ✅ Modelo conceptual claro
- ✅ Lenguaje clínico definido
- ✅ Timeline humanizado especificado
- ✅ Roles y visibilidad definidos

**NO se ha implementado:**
- ❌ Migrations
- ❌ Modelos Eloquent
- ❌ Eventos
- ❌ Controllers
- ❌ Vistas
- ❌ Tests

**Validación necesaria:**
Este modelo debe ser validado conceptualmente antes de proceder a implementación.

---

**Estado:** Diseño completado, pendiente de validación
**Autor:** Claude Code (diseño conceptual)
**Fecha:** 2025-12-25
