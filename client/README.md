# Drum Works — Cliente (Vue 3)

Frontend de la aplicación de gestión de facturación para la academia **Drum Works**. Desarrollado con Vue 3 Composition API, Pinia y Tailwind CSS 4.

---

## Tecnologías

- [Vue 3](https://vuejs.org/) — Composition API con `<script setup>`
- [Vite](https://vitejs.dev/) — Bundler y servidor de desarrollo
- [Pinia](https://pinia.vuejs.org/) — Gestión de estado
- [Vue Router](https://router.vuejs.org/) — Enrutamiento SPA
- [Tailwind CSS 4](https://tailwindcss.com/) — Estilos
- [Axios](https://axios-http.com/) — Peticiones HTTP
- [SweetAlert2](https://sweetalert2.github.io/) — Diálogos y notificaciones


---

## Estructura del proyecto

```
client/
├── src/
│   ├── api/
│   │   └── axios.js            # Instancia de Axios con interceptores
│   ├── assets/
│   │   └── img/                # Imágenes estáticas
│   ├── components/
│   │   ├── buttons/
│   │   │   └── PrimaryButton.vue
│   │   └── ui/
│   │       ├── ClienteComponent.vue   # Fila de cliente (lista)
│   │       ├── FacturaComponent.vue   # Fila de factura (lista)
│   │       ├── ModalCliente.vue       # Modal crear/editar cliente
│   │       ├── ModalFacturaBolo.vue   # Modal nueva factura de bolo
│   │       └── ScreenLoader.vue       # Pantalla de carga
│   ├── stores/
│   │   ├── auth.js             # Autenticación (Sanctum)
│   │   ├── cliente.js          # CRUD de clientes
│   │   └── factura.js          # CRUD de facturas y filtros
│   ├── utils/
│   │   └── swal.js             # Helpers de SweetAlert2
│   ├── views/
│   │   ├── LoginView.vue
│   │   ├── HomeView.vue
│   │   ├── ClientesView.vue
│   │   └── FacturasView.vue
│   ├── App.vue
│   └── main.js
├── public/
│   └── favicon.ico
├── .env.example
├── .env.production             # Variables de entorno para producción
├── tailwind.config.js
├── vite.config.js
└── package.json
```

---

## Variables de entorno

Crea un archivo `.env` en la raíz de `client/` basándote en `.env.example`:

```env
VITE_APP_BACKEND_URL=http://localhost:8000/api
```

Para producción usa `.env.production`:

```env
VITE_APP_BACKEND_URL=https://tudominio.com/api
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

El servidor de desarrollo arranca en `http://localhost:5173` y hace proxy de las peticiones API al backend en `http://localhost:8000`.

---

## Despliegue

1. Configura `.env.production` con la URL del backend en producción
2. Genera el build:
   ```bash
   npm run build
   ```
3. Sube el contenido de la carpeta `dist/` a la carpeta `public/` del servidor Laravel

---

## Funcionalidades

### Autenticación
- Login con email y contraseña mediante Laravel Sanctum
- Token almacenado en memoria (Pinia store)
- Rutas protegidas con guards de Vue Router

### Gestión de Clientes
- Listado con filtro por tipo: **Alumnos** (clases) y **Bolos** (conciertos)
- Crear, editar y eliminar clientes
- Campos específicos según el tipo de cliente

### Gestión de Facturas
- Listado con filtros por mes y estado de pago
- **Serie C** — Generación masiva de facturas mensuales para todos los alumnos
- **Serie B** — Creación manual de facturas para bolos (conciertos)
- Cambio de estado entre *Pendiente* y *Pagada* con un clic
- Descarga de factura en PDF
- Eliminación de facturas

---

## Diseño responsive

La interfaz está adaptada para móvil, tablet y escritorio:
- Sidebar con overlay en móvil
- Listas de clientes y facturas en formato tarjeta en móvil y tabla en escritorio
- Modales adaptados a pantalla completa en móvil