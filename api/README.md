# Drum Works — API (Laravel 13)

Backend REST de la aplicación de gestión de facturación para la academia **Drum Works**. Desarrollado con Laravel 13 y autenticación mediante Laravel Sanctum.

---

## Tecnologías

- [Laravel 13](https://laravel.com/) — Framework PHP
- [Laravel Sanctum](https://laravel.com/docs/sanctum) — Autenticación por token
- [Laravel DomPDF](https://github.com/barryvdh/laravel-dompdf) — Generación de PDFs
- MySQL 8.0

**Requisito:** PHP 8.4

---

## Estructura del proyecto

```
api/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       ├── AuthController.php
│   │       ├── ClienteController.php
│   │       └── FacturaController.php
│   └── Models/
│       ├── User.php
│       ├── Cliente.php
│       └── Factura.php
├── database/
│   └── migrations/
├── resources/
│   └── views/
│       └── pdf/
│           └── factura.blade.php   # Plantilla PDF de factura
├── routes/
│   ├── api.php                     # Rutas de la API
│   └── web.php
├── storage/
├── .env.example
└── composer.json
```

---

## Variables de entorno

Copia `.env.example` a `.env` y configura:

```env
APP_NAME="Drum Works"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=drum_works
DB_USERNAME=root
DB_PASSWORD=

SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
```

---

## Instalación y desarrollo

```bash
# Instalar dependencias
composer install

# Generar clave de la aplicación
php artisan key:generate

# Ejecutar migraciones
php artisan migrate

# Servidor de desarrollo
php artisan serve
```

La API estará disponible en `http://localhost:8000/api`.

---

## Despliegue en producción

```bash
# Instalar dependencias sin paquetes de desarrollo
php8.4-cli /ruta/a/composer.phar install --no-dev --optimize-autoloader

# Configurar .env de producción y generar clave
php8.4-cli artisan key:generate

# Ejecutar migraciones
php8.4-cli artisan migrate --force

# Cachear configuración, rutas y vistas
php8.4-cli artisan config:cache
php8.4-cli artisan route:cache
php8.4-cli artisan view:cache

# Permisos de carpetas
chmod -R 775 storage bootstrap/cache
```

El Document Root del servidor web debe apuntar a la carpeta `public/`.

---

## Endpoints de la API

Todas las rutas protegidas requieren el header:
```
Authorization: Bearer {token}
```

### Autenticación

| Método | Ruta | Descripción |
|--------|------|-------------|
| POST | `/api/registro` | Registrar usuario |
| POST | `/api/login` | Iniciar sesión |
| POST | `/api/logout` | Cerrar sesión |
| GET | `/api/usuario` | Datos del usuario autenticado |

### Clientes

| Método | Ruta | Descripción |
|--------|------|-------------|
| GET | `/api/clientes` | Listar todos los clientes |
| POST | `/api/clientes` | Crear cliente |
| GET | `/api/clientes/{id}` | Ver cliente |
| PUT | `/api/clientes/{id}` | Actualizar cliente |
| DELETE | `/api/clientes/{id}` | Eliminar cliente |

Los clientes tienen dos tipos:
- `alumno` — Alumnos de clases, con cuota mensual y curso/grupo
- `bolo` — Clientes para conciertos, con precio base

### Facturas

| Método | Ruta | Descripción |
|--------|------|-------------|
| GET | `/api/facturas` | Listar todas las facturas |
| POST | `/api/facturas` | Crear factura |
| POST | `/api/facturas/generar-masiva` | Generar facturas mensuales para todos los alumnos |
| PUT | `/api/facturas/{id}/estado` | Cambiar estado (pendiente/pagada) |
| DELETE | `/api/facturas/{id}` | Eliminar factura |
| GET | `/api/facturas/{id}/pdf` | Descargar factura en PDF |

Las facturas tienen dos series:
- **Serie C** — Clases mensuales, generadas de forma masiva
- **Serie B** — Bolos (conciertos), creadas manualmente con IVA 10% e IRPF 15%

---

## Modelo de datos

### Cliente
| Campo | Tipo | Descripción |
|-------|------|-------------|
| nombre | string | Nombre completo o razón social |
| nif_cif | string | Identificación fiscal |
| email | string | |
| telefono | string | |
| direccion | string | Dirección fiscal |
| codigo_postal | string | |
| localidad | string | |
| provincia | string | |
| tipo | enum | `alumno` o `bolo` |
| curso | string | Solo para alumnos |
| cuota_mensual | decimal | Cuota mensual o precio base bolo |

### Factura
| Campo | Tipo | Descripción |
|-------|------|-------------|
| codigo | string | Código único (ej: `001C/2025`) |
| serie | enum | `C` (clases) o `B` (bolos) |
| cliente_id | foreign key | |
| concepto | string | Descripción del servicio |
| fecha_emision | date | |
| fecha_evento | date | Solo para bolos |
| subtotal | decimal | Base imponible |
| iva_porcentaje | decimal | |
| iva_monto | decimal | |
| irpf_porcentaje | decimal | |
| irpf_monto | decimal | |
| monto | decimal | Total a percibir |
| estado | enum | `pendiente` o `pagada` |
