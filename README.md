# Aura v2 - Sistema de Gestión Clínica Dental

Sistema de gestión para clínicas dentales con arquitectura CQRS y Event Sourcing.

## 🚀 Características Principales

### CRUD Completo de Catálogos
- **Pacientes**: Crear, editar y gestionar información de pacientes
- **Profesionales**: Gestión completa de profesionales con roles y estados
- **Tratamientos**: Catálogo de tratamientos con precios de referencia

### Arquitectura Técnica
- **CQRS**: Separación de comandos y consultas
- **Event Sourcing**: Historial completo de eventos del dominio
- **Outbox Pattern**: Procesamiento asíncrono de eventos
- **UUID Primary Keys**: Identificadores únicos universales
- **Soft Deletes**: Borrado lógico sin pérdida de datos

## 🎨 UI/UX - Diseño Aura

### Sistema Visual Canónico
- **Grid Layout**: Columnas fijas para alineación perfecta
  - Contenido: `1fr` (flexible)
  - Estado: `110px` (fijo)
  - Acciones: `80px` (fijo)
- **Alpine.js**: Reactividad client-side con filtrado en tiempo real
- **HTMX**: Actualizaciones dinámicas sin recarga de página
- **Badges de Estado**: Posición fija independiente del contenido

### Patrón de Interacción
```
Hover en item → Botones aparecen (fade in)
  ↓
Click editar → Modal se abre con datos actuales
  ↓
Modificar y guardar → HTMX PATCH al servidor
  ↓
Respuesta con HTML fresco → Swap automático
  ↓
Modal se cierra → Lista actualizada
```

## 📦 Instalación

```bash
# Clonar repositorio
git clone https://github.com/mnogueres/aura-v2.git
cd aura-v2

# Instalar dependencias
composer install
npm install

# Configurar entorno
cp .env.example .env
php artisan key:generate

# Configurar base de datos en .env
# DB_CONNECTION=mysql
# DB_DATABASE=aura_laravel
# DB_USERNAME=root
# DB_PASSWORD=

# Ejecutar migraciones
php artisan migrate

# Compilar assets
npm run build

# Iniciar servidor
php artisan serve
```

## 🗂️ Estructura del Proyecto

### Modelos de Dominio
```
app/Models/
├── Patient.php                 # Pacientes
├── Professional.php            # Profesionales (write model)
├── ClinicalProfessional.php   # Profesionales (read model)
├── TreatmentDefinition.php    # Tratamientos (write model)
├── ClinicalTreatmentDefinition.php  # Tratamientos (read model)
└── OutboxEvent.php            # Eventos de dominio
```

### Controladores
```
app/Http/Controllers/
├── PatientController.php                # CRUD Pacientes
├── ProfessionalWorkspaceController.php  # CRUD Profesionales
├── TreatmentCatalogController.php       # CRUD Tratamientos
└── PatientWorkspaceController.php       # Workspace de paciente
```

### Servicios de Dominio
```
app/Services/
├── ClinicalProfessionalService.php      # Lógica de negocio Profesionales
├── ClinicalTreatmentCatalogService.php  # Lógica de negocio Tratamientos
└── OutboxEventConsumer.php              # Procesamiento de eventos
```

### Vistas (Blade + Alpine.js + HTMX)
```
resources/views/
├── patients/
│   ├── index.blade.php                  # Listado de pacientes
│   └── partials/
│       ├── _patients_content.blade.php  # Contenido dinámico
│       ├── _new_patient_modal.blade.php # Modal crear
│       └── _edit_patient_modal.blade.php # Modal editar
├── workspace/
│   ├── professionals/
│   │   ├── index.blade.php
│   │   └── partials/
│   │       ├── _professionals_content.blade.php
│   │       ├── _new_professional_modal.blade.php
│   │       └── _edit_professional_modal.blade.php
│   └── treatments/
│       ├── index.blade.php
│       └── partials/
│           ├── _treatments_content.blade.php
│           ├── _new_treatment_modal.blade.php
│           └── _edit_treatment_modal.blade.php
```

## 🔧 Tecnologías

- **Backend**: Laravel 11
- **Base de datos**: MySQL
- **Frontend**: Alpine.js 3.x, HTMX 1.9
- **CSS**: Custom Aura Design System
- **Build**: Vite
- **Testing**: PHPUnit, Pest

## 📝 Convenciones de Código

### Commits
Seguimos Conventional Commits:
```
feat: Nueva funcionalidad
fix: Corrección de bug
docs: Cambios en documentación
refactor: Refactorización de código
test: Añadir o modificar tests
```

Todos los commits incluyen:
```
🤖 Generated with Claude Code (https://claude.com/claude-code)
Co-Authored-By: Claude Sonnet 4.5 <noreply@anthropic.com>
```

### Arquitectura HTMX
- **Target**: Siempre usar IDs específicos (`#professionals-content`)
- **Swap**: Usar `outerHTML` para reemplazar contenido completo
- **CSRF**: Incluir token en `hx-headers` para PATCH/DELETE
- **Modals**: Cerrar automáticamente con `hx-on::after-request`

## 📊 Estado del Proyecto

### Fase Actual: FASE 21.3 ✅
**CRUD + HTMX Stabilization - COMPLETADO**

### Funcionalidades Completadas

#### FASE 19 - Live Product ✅
- Pacientes en base de datos real
- Paginación de 8 items por página
- Búsqueda client-side con Alpine.js

#### FASE 20 - Catálogo de Tratamientos ✅
- Crear, editar, activar/desactivar tratamientos
- Precio de referencia configurable
- Eliminación condicional (solo si nunca usado)

#### FASE 21 - Catálogo de Profesionales ✅
- Crear, editar, activar/desactivar profesionales
- Roles: Odontólogo/a, Higienista, Asistente, Otro
- Event Sourcing completo con proyecciones

#### FASE 22 - Normalización Canónica ✅
- Estructura idéntica en 3 páginas (Pacientes, Profesionales, Tratamientos)
- Grid layout con columnas fijas
- Badges de estado alineados verticalmente
- Paginación consistente (8 items/página)

#### FASE 21.3 - HTMX Stabilization ✅
- Modales CRUD completos para las 3 entidades
- Respuestas HTMX unificadas
- Zero `htmx:targetError` en consola
- CSRF tokens correctamente configurados
- Modales se cierran automáticamente tras éxito

### Bugs Conocidos
Ninguno actualmente. Sistema estable.

### Próximas Fases
- FASE 23: Dashboard y estadísticas
- FASE 24: Sistema de citas
- FASE 25: Facturación

## 🧪 Testing

```bash
# Ejecutar todos los tests
php artisan test

# Ejecutar tests específicos
php artisan test --filter=ClinicalProjectionTest

# Tests con coverage
php artisan test --coverage
```

Estado actual: **268/295 tests passing (90.8%)**

## 🤝 Contribución

El proyecto Aura es desarrollado con asistencia de Claude Code. Para contribuir:

1. Fork el repositorio
2. Crea una rama para tu feature (`git checkout -b feat/nueva-funcionalidad`)
3. Commit tus cambios siguiendo Conventional Commits
4. Push a la rama (`git push origin feat/nueva-funcionalidad`)
5. Abre un Pull Request

## 📄 Licencia

Este proyecto es privado y está bajo licencia propietaria.

---

**Desarrollado con** 🤖 [Claude Code](https://claude.com/claude-code) **y** ❤️ **por el equipo de Aura**
