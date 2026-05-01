---
version: alpha
name: SGCI DRE Huánuco
description: Sistema de Gestión de Control Interno - Dirección Regional de Educación de Huánuco
colors:
  primary: "#2563eb"
  primary-strong: "#4f46e5"
  primary-deep: "#4338ca"
  surface-dark: "#0f172a"
  surface-dark-alt: "#1e293b"
  surface: "#ffffff"
  surface-muted: "#f8fafc"
  surface-tint-blue: "#eff6ff"
  surface-tint-indigo: "#eef2ff"
  text-primary: "#0f172a"
  text-secondary: "#475569"
  text-muted: "#64748b"
  text-placeholder: "#94a3b8"
  text-on-dark: "#ffffff"
  success: "#059669"
  success-soft: "#ecfdf5"
  warning: "#d97706"
  warning-soft: "#fffbeb"
  danger: "#dc2626"
  danger-soft: "#fef2f2"
  module-rrhh: "#059669"
  module-visitas: "#9333ea"
  module-vehiculos: "#0891b2"
  module-citas: "#db2777"
  module-papeletas: "#d97706"
  module-patrimonio: "#334155"
typography:
  display:
    fontFamily: Inter
    fontSize: 3rem
    fontWeight: 900
    lineHeight: 1.1
    letterSpacing: -0.02em
  h1:
    fontFamily: Inter
    fontSize: 2.25rem
    fontWeight: 800
    lineHeight: 1.15
  h2:
    fontFamily: Inter
    fontSize: 1.875rem
    fontWeight: 700
  h3:
    fontFamily: Inter
    fontSize: 1.25rem
    fontWeight: 700
  body-md:
    fontFamily: Inter
    fontSize: 1rem
    fontWeight: 400
    lineHeight: 1.5
  body-sm:
    fontFamily: Inter
    fontSize: 0.875rem
    fontWeight: 500
  label-caps:
    fontFamily: Inter
    fontSize: 0.75rem
    fontWeight: 700
    letterSpacing: 0.1em
rounded:
  sm: 8px
  md: 12px
  lg: 16px
  xl: 24px
  hero: 32px
spacing:
  xs: 4px
  sm: 8px
  md: 16px
  lg: 24px
  xl: 32px
  2xl: 48px
components:
  button-primary:
    backgroundColor: "{colors.primary}"
    textColor: "{colors.text-on-dark}"
    typography: "{typography.body-sm}"
    rounded: "{rounded.md}"
    padding: "12px 32px"
  input:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.text-primary}"
    rounded: "{rounded.md}"
    padding: "10px 16px"
  select:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.text-primary}"
    rounded: "{rounded.md}"
    padding: "6px 12px"
  card:
    backgroundColor: "{colors.surface}"
    rounded: "{rounded.lg}"
    padding: "24px"
  modal:
    backgroundColor: "{colors.surface}"
    rounded: "{rounded.lg}"
  sidebar:
    backgroundColor: "{colors.surface-dark}"
    textColor: "{colors.text-on-dark}"
    width: "288px"
---

## Overview

Profesionalismo institucional con calidez digital. El SGCI DRE Huánuco es una aplicación de gestión interna para una entidad pública peruana — requiere seriedad y claridad sin frivolidad. La identidad visual se construye sobre gradientes azul → índigo como firma de marca, slate oscuro (`#0f172a`) como anclaje de autoridad en el chrome, y superficies blancas/slate claras para el contenido.

El sistema tiene nueve módulos funcionales bien diferenciados (Ocurrencias, RRHH, Visitas, Vehículos, Patrimonio, Citas, Bienestar, Papeletas, Usuarios), cada uno con su propio color de acento. Esto mejora la orientación cognitiva del usuario institucional que navega entre dominios distintos.

Tecnología: Laravel 10 + Vue 3 + Inertia.js + Tailwind CSS v4. La fuente única es **Inter** servida desde Bunny Fonts.

## Colors

La paleta se divide en cinco capas: marca, superficies, texto, feedback y módulos.

**Marca:**
- **primary (`#2563eb`)** — blue-600. Inicio del gradiente principal. Aparece en botones CTA, items activos del sidebar, headers de modal y el logo del login.
- **primary-strong (`#4f46e5`)** — indigo-600. Fin del gradiente principal (`from-blue-600 to-indigo-600`). Comunica progresión y profundidad.
- **primary-deep (`#4338ca`)** — indigo-700. Estado hover del gradiente primario.

**Superficies:**
- **surface-dark (`#0f172a`)** / **surface-dark-alt (`#1e293b`)** — slate-900/800. Conforman el gradiente vertical del sidebar (`from-slate-900 via-slate-800 to-slate-900`) y el header móvil. Aportan gravedad institucional.
- **surface (`#ffffff`)** — fondo de cards, modals e inputs. Contraste máximo sobre el page background.
- **surface-muted (`#f8fafc`)** — slate-50. Color base del `<body>` (`bg-slate-50`).
- **surface-tint-blue (`#eff6ff`)** / **surface-tint-indigo (`#eef2ff`)** — blue-50/indigo-50. Conforman el gradiente del page background (`from-slate-50 via-blue-50 to-indigo-50`). Añaden calor sutil sin distraer.

**Texto:**
- **text-primary (`#0f172a`)** — slate-900. Titulares, valores de stat cards, texto de peso crítico.
- **text-secondary (`#475569`)** — slate-600. Texto de soporte, labels de formulario.
- **text-muted (`#64748b`)** — slate-500. Metadatos, timestamps, placeholders visibles.
- **text-placeholder (`#94a3b8`)** — slate-400. Placeholder de inputs.
- **text-on-dark (`#ffffff`)** — blanco puro sobre superficies oscuras y gradientes de marca.

**Feedback:**
- **success (`#059669`)** / **success-soft (`#ecfdf5`)** — emerald-600/50. Confirma operaciones exitosas.
- **warning (`#d97706`)** / **warning-soft (`#fffbeb`)** — amber-600/50. Alertas y estados pendientes.
- **danger (`#dc2626`)** / **danger-soft (`#fef2f2`)** — red-600/50. Errores de validación y estados críticos.

**Color-coding por módulo:**
Cada módulo usa un par de colores para su ítem activo en sidebar, el borde lateral de sus stat cards, los headers de sus modals y el ícono de acceso rápido en el Dashboard.

| Módulo | Token | Hex |
|---|---|---|
| RRHH / Control Personal | module-rrhh | `#059669` (emerald-600) |
| Visitas Externas | module-visitas | `#9333ea` (purple-600) |
| Vehículos / Asistencia | module-vehiculos | `#0891b2` (cyan-600) |
| Citas | module-citas | `#db2777` (pink-600) |
| Papeletas de Salida | module-papeletas | `#d97706` (amber-600) |
| Patrimonio / Activos | module-patrimonio | `#334155` (slate-700) |
| Dashboard / Ocurrencias | primary → primary-strong | `#2563eb` → `#4f46e5` |
| Bienestar Social | module-visitas → primary-strong | `#9333ea` → `#4f46e5` |
| Usuarios (Admin) | primary-strong → module-visitas | `#4f46e5` → `#9333ea` |

## Typography

**Inter** es la única familia tipográfica. Se carga desde Bunny Fonts en pesos 400, 500, 600, 700 y 800. El peso 900 (`font-black`) se usa puntualmente en el hero del dashboard y el display del login.

El sistema aplica `font-sans antialiased` al `<body>`, lo que hace que Inter sea la tipografía global por defecto en Tailwind v4.

Escala de uso por contexto:
- **display** — Hero del dashboard (nombre bienvenida, titular principal). `font-black tracking-tight`.
- **h1** — Título principal de página y hero del login. `font-extrabold`.
- **h2** — Valores de stat cards, títulos de sección. `font-bold`.
- **h3** — Títulos de modals y paneles. `font-bold`.
- **body-md** — Texto de párrafo, descripción de items. `font-normal`.
- **body-sm** — Labels de formulario, items de navegación, texto de botones, contenido de tabla. `font-medium`.
- **label-caps** — Etiquetas de metadatos en mayúsculas (`uppercase tracking-widest`), badges de estado, columnas de tabla administrativas. `font-bold`.

## Layout

**Shell de la aplicación:**
- Sidebar fijo en desktop: `w-72` (288px), colapsable a `w-20` (80px). Sticky, altura `h-screen`.
- Contenido principal: `flex-1 overflow-auto`. Padding interno del contenedor de páginas: `p-6` o `p-8`.
- Ancho máximo del contenido: `max-w-7xl mx-auto` en páginas de lista/dashboard.
- Header móvil: `h-16`, `fixed top-0`, misma paleta oscura que el sidebar. Oculto en `md:hidden` / visible en `md:flex`.

**Page background:** `bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50`. El gradiente es muy sutil — prácticamente blanco — y actúa como textura de fondo sin competir con el contenido.

**Sidebar gradient:** `bg-gradient-to-b from-slate-900 via-slate-800 to-slate-900`. Logo en la cabecera dentro de un cuadrado con gradiente primario (`from-blue-600 to-indigo-700`).

**Espaciado vertical entre secciones:** `space-y-6` o `space-y-8` en cuerpos de página y modals. Breathing room generoso, apropiado para uso administrativo diario.

## Elevation & Depth

El sistema usa una escala de 5 niveles para comunicar jerarquía espacial:

| Nivel | Clase | Uso |
|---|---|---|
| 0 | (sin sombra) | Elementos inline, separadores |
| 1 | `shadow-sm` | Cards en reposo |
| 2 | `shadow-md` | Cards en hover, paginación |
| 3 | `shadow-lg` | Botones primarios, íconos de módulo en Dashboard |
| 4 | `shadow-2xl` | Modals, sidebar, hero del dashboard |

**Glow coloreado (estado activo de módulo):**
Los items activos del sidebar y los íconos de acceso rápido aplican un halo del color del módulo: `shadow-lg shadow-{module}-500/30 ring-1 ring-{module}-400/50`. Esto crea presencia visual sin bordes duros.

**Glassmorphism (overlays sobre imágenes):**
En el login y el hero del dashboard, donde el contenido se superpone a una imagen de fondo (`/images/login-bg.png`), se aplica `bg-gradient-to-br from-blue-900/80 via-indigo-900/80 to-purple-900/80` o `from-blue-900/95 via-blue-900/70 to-indigo-900/40`. Los elementos flotantes menores usan `bg-white/10 backdrop-blur-xl border border-white/20`.

**Modal backdrop:** `bg-black/40 backdrop-blur-sm`. Suficiente para separar el modal del contexto sin oscurecer completamente.

## Shapes

El radio base del sistema es **12px** (`rounded-xl`). La escala es progresiva y semántica:

| Token | Valor | Uso |
|---|---|---|
| sm | 8px (`rounded-lg`) | Íconos de módulo en sidebar colapsado, badges de estado, scrollbar thumb |
| **md** | **12px (`rounded-xl`)** | **Base del sistema:** inputs, botones, items de navegación, paginación, elementos interactivos |
| lg | 16px (`rounded-2xl`) | Modals, cards principales, hero de Login, accesos rápidos del Dashboard |
| xl | 24px (`rounded-3xl`) | Logo wrapper en el login |
| hero | 32px (`rounded-[2rem]`) | Hero card del Dashboard (borde de contenedor de imagen) |
| pill | 9999px (`rounded-full`) | Avatares de usuario, dots de estado en línea |

**Regla de aplicación:**
- Elementos interactivos pequeños y controles de formulario: `md` (12px).
- Contenedores de información (cards, paneles, modals): `lg` (16px).
- Elementos de identidad de marca (logo, hero): `xl` o `hero`.
- No mezclar radios adyacentes en el mismo componente compuesto — los hijos deben usar un nivel menor que el contenedor.

## Components

### Botón primario

```html
<button class="cursor-pointer inline-flex items-center justify-center px-8 py-3
               rounded-xl text-sm font-bold text-white
               bg-gradient-to-r from-blue-600 to-indigo-600
               hover:from-blue-700 hover:to-indigo-700
               focus:ring-4 focus:ring-blue-300
               shadow-lg hover:shadow-xl
               transition-all duration-300 active:scale-95">
```

### Input estándar

```html
<input class="w-full px-4 py-2.5 border-2 rounded-xl
              border-slate-200 bg-white text-slate-900
              focus:ring-4 focus:ring-{module-color}/20 focus:border-{module-color}
              transition-all outline-none placeholder:text-slate-400 cursor-pointer" />
```

### Select estándar

```html
<select class="border-2 border-slate-200 rounded-xl px-3 py-1.5 text-sm
               bg-white text-slate-900
               focus:ring-4 focus:ring-{module-color}/20 focus:border-{module-color}
               transition-all outline-none cursor-pointer">
```

### Botón de acciones (tabla/modal)

```html
<button class="cursor-pointer px-4 py-2 bg-blue-50 hover:bg-blue-100
               text-blue-600 font-bold rounded-xl transition-all flex items-center gap-2">
  <Icon class="w-4 h-4" />
  Acción
</button>
```

**Regla del focus ring:** el color del ring siempre lleva opacidad `/20` (`focus:ring-{color}/20`). Esto genera un halo translúcido que parece un brillo suave alrededor del campo, en lugar de un segundo borde sólido. Sin el `/20`, el `focus:ring-4` y el `border-2` se suman visualmente y parecen un borde doble.

`outline-none` es obligatorio en todos los `<input>`, `<select>` y `<textarea>` para eliminar el borde negro nativo del navegador.

### Stat card con acento lateral de módulo

```html
<div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-{module}-500">
  <p class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">
    Etiqueta</p>
  <p class="text-3xl font-bold text-slate-900">{{ valor }}</p>
  <div class="p-3 bg-{module}-100 rounded-lg">
    <Icon class="w-8 h-8 text-{module}-600" />
  </div>
</div>
```

### Modal

```html
<!-- Backdrop -->
<div class="fixed inset-0 bg-black/40 backdrop-blur-sm" />
<!-- Panel -->
<div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-3xl max-h-[90vh]
            flex flex-col overflow-hidden">
  <!-- Header con gradiente del módulo -->
  <div class="bg-gradient-to-r from-{module}-600 to-{module-alt}-600 px-6 py-4">
    <h3 class="text-xl font-bold text-white">Título</h3>
  </div>
  <!-- Body -->
  <div class="p-6 space-y-6 overflow-y-auto flex-1">
    <!-- contenido -->
  </div>
</div>
```

### Sidebar item activo

```html
<!-- Activo -->
<a class="flex items-center gap-3 px-4 py-3.5 rounded-xl text-sm font-semibold
          bg-gradient-to-r from-{module}-600 to-{module-alt}-600 text-white
          shadow-lg shadow-{module}-500/30 ring-1 ring-{module}-400/50">

<!-- Inactivo -->
<a class="flex items-center gap-3 px-4 py-3.5 rounded-xl text-sm font-semibold
          text-slate-300 hover:bg-slate-800/80 hover:text-white
          transition-colors duration-200">
```

### Logo sobre fondo oscuro

```html
<!-- El PNG del logo se invierte a blanco cuando va sobre surfaces oscuras -->
<img src="/images/logo.png" alt="DRE Huánuco"
     class="h-10 w-10 object-contain brightness-0 invert" />
```

## Do's and Don'ts

**Hacer:**
- Añadir `outline-none` a todos los `<input>`, `<select>` y `<textarea>` para eliminar el borde negro nativo del navegador al hacer foco.
- Añadir siempre `cursor-pointer` a todos los elementos interactivos: `<button>`, `<a>`, `<label>`, `<input type="checkbox">`, `<input type="radio">`, y cualquier elemento con `@click`. Esto proporciona retroalimentación visual inmediata al usuario indicando que el elemento es cliqueable.
- Usar siempre `focus:ring-{color}/20` (con opacidad al 20%) en el ring de foco. Esto crea un halo suave en lugar de un segundo borde sólido. Ejemplo: `focus:ring-purple-500/20`, `focus:ring-emerald-500/20`.
- Usar gradientes (`bg-gradient-to-r` o `bg-gradient-to-br`) para todos los elementos de marca: botones primarios, headers de modals, hero, sidebar. Los fondos sólidos planos están reservados para cards de contenido.
- Aplicar el color del módulo correspondiente al item activo del sidebar, al border-l-4 de stat cards, al header del modal, al ícono de acceso rápido y a los badges de ese módulo.
- Mantener `rounded-xl` (12px) como radio por defecto en inputs y botones; subir a `rounded-2xl` solo para contenedores de información.
- Aplicar `brightness-0 invert` al logo (`/images/logo.png`) cuando se renderiza sobre surfaces oscuras o gradientes de marca.
- Usar `font-bold` o `font-semibold` en labels de formulario (`text-slate-700`), y `font-medium` en texto de inputs y navegación.
- Reservar `shadow-2xl` para el chrome del sistema (sidebar, modals) y el hero del dashboard. Las cards de contenido usan `shadow-sm` / `shadow-md`.

**No hacer:**
- No introducir colores fuera de la paleta Tailwind default sin registrarlos primero en la sección `colors` de este archivo.
- No mezclar familias tipográficas. Inter es la única fuente del sistema; no cargar Google Fonts adicionales.
- No crear nuevos módulos sin asignarles un par de colores y actualizando la tabla de color-coding en este archivo.
- No usar `border-radius` personalizados arbitrarios cuando uno de los cinco tokens (`sm`, `md`, `lg`, `xl`, `hero`) es suficiente.
- No aplicar sombras de nivel 4 (`shadow-2xl`) a cards regulares de contenido — reservadas para el chrome del sistema.
- No añadir segundo gradiente de página (`bg-gradient-to-br`) a secciones interiores; el gradiente de fondo ya existe en el `<body>` y agregar otro crea ruido visual.
