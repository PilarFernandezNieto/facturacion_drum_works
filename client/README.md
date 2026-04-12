# DrummerWorks — Cliente (Vue 3)

Frontend de la aplicación de gestión de facturación para **DrummerWorks**, negocio de un músico autónomo que combina clases de batería y actuaciones en conciertos. Desarrollado con Vue 3 Composition API, TanStack Query y Tailwind CSS 4.

---

## Tecnologías

- [Vue 3](https://vuejs.org/) — Composition API con `<script setup>`
- [Vite](https://vitejs.dev/) — Bundler y servidor de desarrollo
- [TanStack Query](https://tanstack.com/query/latest) — Caché y sincronización de datos del servidor
- [Pinia](https://pinia.vuejs.org/) — Estado de sesión (autenticación)
- [Vue Router](https://router.vuejs.org/) — Enrutamiento SPA con guards
- [Tailwind CSS 4](https://tailwindcss.com/) — Estilos
- [Axios](https://axios-http.com/) — Peticiones HTTP con interceptores
- [SweetAlert2](https://sweetalert2.github.io/) — Diálogos y notificaciones
- [JSZip](https://stuk.github.io/jszip/) — Descarga masiva de PDFs en ZIP

---

## Estructura del proyecto

```
client/src/
├── api/
│   └── axios.js                     # Instancia de Axios con interceptores (token Bearer, 401)
├── composables/
│   ├── useClientes.js               # Queries y mutaciones de clientes (TanStack)
│   ├── useFacturas.js               # Queries, mutaciones y descarga de PDFs (TanStack)
│   └── usePaginacion.js             # Paginación reactiva reutilizable
├── components/
│   ├── buttons/
│   │   └── PrimaryButton.vue
│   └── ui/
│       ├── AppHeader.vue
│       ├── AppSidebar.vue           # Navegación con dropdowns (Clientes / Facturas)
│       ├── ClienteComponent.vue     # Fila de cliente
│       ├── FacturaComponent.vue     # Fila de factura
│       ├── ModalCliente.vue         # Crear / editar cliente
│       ├── ModalFacturaBolo.vue     # Nueva factura de bolo (Serie B)
│       ├── ModalHistorialCliente.vue # Historial de facturas de un cliente
│       ├── PaginacionComponent.vue  # Paginación con ellipsis
│       ├── ScreenLoader.vue
│       └── TarjetaDashboard.vue
├── stores/
│   └── auth.js                      # Token y sesión de usuario (Pinia)
├── utils/
│   └── swal.js                      # Helpers de SweetAlert2
├── views/
│   ├── auth/
│   │   ├── LoginView.vue
│   │   └── RegistroView.vue
│   ├── DashboardView.vue            # Estadísticas del mes
│   ├── ClientesView.vue             # Lista de alumnos o bolos según ruta
│   └── FacturasView.vue             # Lista de facturas C o B según ruta
├── router/index.js
├── App.vue
└── main.js
```

---

## Rutas de la aplicación

| Ruta | Nombre | Descripción |
|------|--------|-------------|
| `/` | `dashboard` | Panel de estadísticas |
| `/clientes/alumnos` | `alumnos` | Listado de alumnos de clases |
| `/clientes/bolos` | `bolos` | Listado de clientes de bolos |
| `/facturas/clases` | `facturas-clases` | Facturas Serie C (clases) |
| `/facturas/bolos` | `facturas-bolos` | Facturas Serie B (bolos) |
| `/login` | `login` | Acceso |

Las rutas `/clientes` y `/facturas` redirigen automáticamente a su sub-ruta por defecto.

---

## Variables de entorno

Crea `.env` basándote en `.env.example`:

```env
VITE_APP_BACKEND_URL=http://localhost:8000
```

Para producción usa `.env.production`:

```env
VITE_APP_BACKEND_URL=https://tudominio.com
```

---

## Instalación y desarrollo

```bash
# Instalar dependencias
npm install

# Servidor de desarrollo
npm run dev

# Build de producción
npm run build
```

El servidor de desarrollo arranca en `http://localhost:5173`.

---

## Despliegue

1. Configura `.env.production` con la URL del backend
2. Genera el build:
   ```bash
   npm run build
   ```
3. Sube el contenido de `dist/` a la carpeta `public/` del servidor Laravel

---

## Arquitectura de datos

Los datos del servidor se gestionan con **TanStack Query**, no con Pinia.

| Composable | Query key | `staleTime` | Descripción |
|---|---|---|---|
| `useClientes()` | `['clientes']` | 5 min | Lista completa; `alumnos` y `bolos` son computed |
| `useFacturas()` | `['facturas']` | 1 min | Lista completa; el filtrado por serie ocurre en el cliente |

Las mutaciones (`useAgregarCliente`, `useCambiarEstado`, etc.) invalidan su query key al completarse. `useCambiarEstado` aplica **actualización optimista**: el cambio de estado es instantáneo en UI y se revierte si el servidor falla.

**Pinia** solo gestiona la sesión de usuario (`stores/auth.js`): token en `localStorage`, usuario en memoria.

---

## Funcionalidades

### Autenticación
- Login con email y contraseña mediante Laravel Sanctum
- Token Bearer almacenado en `localStorage`
- Rutas protegidas con guards de Vue Router
- Redirección automática a login en error 401

### Clientes
- Vistas separadas para **Alumnos** (`/clientes/alumnos`) y **Bolos** (`/clientes/bolos`)
- Crear, editar y eliminar clientes
- El modal de creación pre-rellena el tipo según la sección activa
- Paginación de 10 registros por página
- Historial de facturas por cliente

### Facturas
- Vistas separadas para **Clases** (`/facturas/clases`) y **Bolos** (`/facturas/bolos`)
- Filtros por mes y estado de pago (locales, no afectan al caché)
- **Serie C** — Botón "Generar Clases (Mes)": crea facturas masivas para todos los alumnos el día 1 de cada mes
- **Serie B** — Botón "Nueva Factura Bolo": creación manual con cálculo de IVA e IRPF en tiempo real
- Cambio de estado Pendiente/Pagada con actualización optimista
- Descarga de PDF individual
- Descarga masiva de PDFs en ZIP (según los filtros activos)
- Paginación de 10 registros por página
- Eliminación con confirmación

### Dashboard
- Tarjetas con: número de alumnos, recaudación del mes (facturas pagadas) y facturas pendientes totales
- Accesos directos a Clases, Bolos y Facturación
