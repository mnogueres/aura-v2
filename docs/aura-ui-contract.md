# Aura UI Contract v1.0

**Estado:** CERRADO Y VINCULANTE
**Fecha:** 2025-12-28
**Alcance:** Páginas de listado (Pacientes, Profesionales, Tratamientos)

---

## Principio Rector

Aura es un producto único con identidad visual consistente. Todas las páginas de listado comparten la misma estructura, layout y comportamiento. No existen excepciones por tipo de entidad o contexto funcional.

---

## Estructura Macro de Página

Toda página de listado sigue esta jerarquía obligatoria:

```
1. Header con descripción operacional
2. CTA (botón acción principal, alineado a la derecha)
3. Buscador (input de filtrado client-side)
4. Listado (grid de filas con estructura fija)
5. Paginación (8 elementos por página, siempre visible)
```

---

## Contrato de Listado: Tabla Conceptual

Cada listado se comporta como una **tabla conceptual** con columnas fijas, aunque se renderice con divs y grid CSS.

**Regla:** El badge de estado y las acciones ocupan columnas de ancho fijo, independientes del contenido textual.

---

## Grid Canónico de Filas

Toda fila de listado usa CSS Grid con 3 columnas:

```css
grid-template-columns: 1fr 110px 80px;
```

**Columnas:**

1. **Contenido (1fr - flexible)**
   - Identidad: nombre principal (h3)
   - Metadata: rol, DNI, precio, etc. (span)

2. **Estado (110px - fijo)**
   - Badge `Activo` o `Inactivo`
   - Centrado horizontal
   - **NO se mueve** según longitud del nombre

3. **Acciones (80px - fijo)**
   - Botones de edición/toggle
   - Alineados a la derecha
   - Visibles en hover (opacity: 0 → 1)

---

## Reglas del Badge Activo/Inactivo

```html
<div class="aura-row__status">
  <span class="aura-status-badge active|inactive">
    Activo | Inactivo
  </span>
</div>
```

**Reglas NO NEGOCIABLES:**

- Badge siempre en columna fija de 110px
- No depende de `justify-content`, `margin-left:auto` ni flow inline
- Verde (`#dcfce7` bg, `#166534` text) para Activo
- Gris (`#f1f5f9` bg, `#64748b` text) para Inactivo
- Padding: `0.25rem 0.625rem`
- Border-radius: `12px`

---

## Reglas de Acciones por Fila

```html
<div class="aura-row__actions" style="opacity: 0; transition: opacity 0.15s ease;">
  <!-- Botones de acción -->
</div>
```

**Comportamiento:**

- Opacity: 0 por defecto
- Opacity: 1 en hover de la fila
- Columna fija de 80px
- Gap entre botones: `0.5rem`
- Botones inline con HTMX + CSRF token

**Acciones estándar:**

- **Pacientes:** Editar
- **Profesionales:** Editar, Toggle Activo/Inactivo
- **Tratamientos:** Editar, Toggle Activo/Inactivo

---

## Paginación Canónica

**Regla fija:** 8 elementos por página en todos los listados.

```php
->paginate(8)
```

**Backend:**
- Laravel paginator en todos los controllers
- Método `paginate(8)`, no `get()`

**Frontend:**
- Botones Anterior/Siguiente
- Números de página visibles
- Siempre presente aunque haya < 8 items

---

## Estados Prohibidos

Los siguientes patrones están **explícitamente prohibidos**:

- ❌ Badge con posición relativa al texto (`justify-content: space-between`)
- ❌ Badge que se mueve según ancho del nombre
- ❌ Filas con estructura diferente entre páginas
- ❌ Acciones permanentemente visibles (sin hover)
- ❌ Paginación > 8 elementos o sin límite (`get()`)
- ❌ Grid con columnas variables o flexbox sin columnas fijas
- ❌ Búsqueda server-side (debe ser client-side con Alpine.js)

---

## Regla de Validación Pre-Commit

Antes de hacer commit de cualquier cambio UI en listados:

1. Verificar que las 3 páginas (Pacientes, Profesionales, Tratamientos) usen el mismo grid
2. Verificar que el badge esté en columna fija de 110px
3. Verificar que todas usen `paginate(8)`
4. Verificar que acciones aparezcan en hover
5. Verificar que NO existan excepciones o variaciones

**Si una verificación falla:** El cambio se considera bug y no se commitea.

---

## Cierre Declarativo

Este documento define **Aura UI v1.0** como cerrada y estable.

Cualquier desviación del contrato aquí definido es una regresión, no una mejora.

El código se adapta al contrato. El contrato no se adapta al código.

---

**Fin del contrato.**
