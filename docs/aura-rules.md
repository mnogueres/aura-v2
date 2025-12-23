Aura — Reglas del Sistema

v1.0 · Clara · Cyan tecnológico · Movimiento orgánico

Aura no es una web.
Aura es una herramienta de trabajo clínico.

Estas reglas definen cómo se decide, no solo cómo se ve.

1. Filosofía General

G-01 — Herramienta, no escaparate
Aura prioriza eficiencia, previsibilidad y calma visual frente a impacto o espectáculo.

G-02 — El vacío es funcional
El espacio libre no es desperdicio: es enfoque y descanso cognitivo.

G-03 — Menos estímulos, más control
Si algo distrae, se elimina. Si algo no aporta, no existe.

2. Reglas de Layout (Estructura)

L-01 — Layout estable
El layout no se adapta dinámicamente al tamaño de pantalla para mostrar más contenido.
La estabilidad visual es prioritaria frente al aprovechamiento máximo del viewport.

L-02 — Cápsula en desktop
En escritorio, el contenido principal vive dentro de una cápsula centrada, con ancho máximo controlado (≈1200px).

L-03 — Superficie total en móvil
En móvil:

no hay márgenes

no hay bordes

no hay radios
El contenido ocupa el 100% del ancho disponible.

3. Reglas de Scroll

S-01 — No scroll infinito
Aura nunca utiliza scroll infinito como patrón de navegación.

S-02 — Scroll como fallback
El scroll solo aparece cuando:

la resolución es insuficiente

o el contenido excede el límite previsto
Nunca como primera solución.

S-03 — Scroll local, no global
Cuando existe scroll, es local al contenido, no a toda la aplicación.

4. Reglas de Paginación

P-01 — Paginación obligatoria
Los listados principales siempre están paginados.

P-02 — Número fijo de ítems
El número de elementos por página es fijo (ej. 8).
No depende del tamaño de pantalla.

P-03 — Paginación visible
Los controles de paginación deben estar siempre accesibles sin perder contexto.

5. Reglas de Jerarquía Visual

V-01 — Nada es negro puro
Aura no usa negro (#000) para texto principal.
Se utilizan tonos profundos y controlados.

V-02 — El dato principal no grita
El dato más importante se reconoce por:

posición

tamaño

ritmo
no por contraste extremo.

V-03 — Jerarquía por capas

Dato principal → text-base / text-highlight

Dato secundario → text-secondary

Dato contextual → text-muted

6. Reglas del Color Cyan

C-01 — Cyan = identidad
El cyan representa acción, estado o identidad.
No se usa como color decorativo.

C-02 — Cyan no es texto principal
El cyan no se utiliza para grandes bloques de texto ni datos críticos.

C-03 — Cyan aparece con intención
Hover, foco, activo, estado positivo.
Nunca como ruido.

7. Reglas de Componentes

CP-01 — Listados
Todo listado:

tiene estados (activo, vacío, error)

tiene paginación

tiene altura controlada

CP-02 — Botones

Primario → cyan

Secundario → neutro

Nunca más de un primario visible por vista

CP-03 — Inputs
Los inputs no compiten visualmente con los datos.
Guían, no protagonizan.

8. Reglas de Mobile

M-01 — Mobile no es desktop reducido
Mobile es acceso rápido, no estación de trabajo.

M-02 — Prioridad táctil
En móvil se prioriza:

tamaño de toque

superficie útil

claridad inmediata

9. Regla de Oro

R-∞ — Si dudas, elimina
Si una decisión no mejora:

claridad

calma

control

entonces no pertenece a Aura.

Estado del sistema

✔ Reglas aprobadas
✔ CSS alineado
✔ Base lista para escalar vistas