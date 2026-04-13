# DrummerWorks — API (Laravel 13)

Backend REST de la aplicación de gestión de facturación para **DrummerWorks**, negocio de un músico autónomo que combina clases de batería y actuaciones en conciertos. Desarrollado con Laravel 13 y autenticación mediante Laravel Sanctum.

---

## Tecnologías

- [Laravel 13](https://laravel.com/) — Framework PHP
- [Laravel Sanctum](https://laravel.com/docs/sanctum) — Autenticación por token Bearer
- [Laravel DomPDF](https://github.com/barryvdh/laravel-dompdf) — Generación de PDFs
- MySQL 8.0 — Base de datos

**Requisito:** PHP 8.4

---

## Estructura del proyecto

```
api/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── AuthController.php       # Login, registro, logout
│   │   │   ├── ClienteController.php    # CRUD clientes (alumnos y bolos)
│   │   │   └── FacturaController.php    # CRUD facturas, generación masiva, PDF
│   │   ├── Middleware/
│   │   │   └── QueryTokenMiddleware.php # Permite pasar token por query param (?token=)
│   │   └── Requests/
│   │       ├── LoginRequest.php
│   │       └── RegisterRequest.php
│   └── Models/
│       ├── User.php
│       ├── Cliente.php
│       └── Factura.php                  # Incluye accessor 'codigo' (ej: 001C/2026)
├── database/
│   ├── migrations/
│   └── database.sqlite
├── resources/
│   └── views/
│       └── pdf/
│           └── factura.blade.php        # Plantilla PDF de factura
├── routes/
│   ├── api.php                          # Rutas de la API
│   └── web.php
├── .env.example
└── composer.json
```

---

## Variables de entorno

Copia `.env.example` a `.env` y ajusta:

```env
APP_NAME="DrummerWorks"
APP_ENV=local
APP_KEY=                    # php artisan key:generate
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=drummerworks
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
composer install --no-dev --optimize-autoloader

# Configurar .env de producción y generar clave
php artisan key:generate

# Ejecutar migraciones
php artisan migrate --force

# Cachear configuración, rutas y vistas
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Permisos de carpetas
chmod -R 775 storage bootstrap/cache
```

El Document Root debe apuntar a la carpeta `public/`.

> **Nota de numeración en producción:** al desplegar por primera vez con facturas previamente emitidas en otro sistema, hay que asegurarse de que `max(numero)` de cada serie arranque desde el número correcto. La forma recomendada es importar los registros anteriores o ejecutar un seeder con el número de partida antes de emitir la primera factura real.

---

## Endpoints de la API

Todas las rutas protegidas requieren:
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
| GET | `/api/clientes` | Listar clientes (acepta `?tipo=alumno\|bolo`) |
| POST | `/api/clientes` | Crear cliente |
| GET | `/api/clientes/{id}` | Ver cliente |
| PUT | `/api/clientes/{id}` | Actualizar cliente |
| DELETE | `/api/clientes/{id}` | Eliminar cliente (elimina también sus facturas) |

Tipos de cliente:
- `alumno` — Alumnos de clases, con cuota mensual fija y grupo/curso
- `bolo` — Clientes para conciertos/actuaciones, con precio base

### Facturas

| Método | Ruta | Descripción |
|--------|------|-------------|
| GET | `/api/facturas` | Listar todas las facturas (con cliente eager-loaded) |
| POST | `/api/facturas` | Crear factura manual (Serie B) |
| POST | `/api/facturas/generar-masiva` | Generar facturas del mes para todos los alumnos (Serie C) |
| PUT | `/api/facturas/{id}/estado` | Cambiar estado (`pendiente` / `pagada`) |
| DELETE | `/api/facturas/{id}` | Eliminar factura |
| GET | `/api/facturas/{id}/pdf` | Descargar factura en PDF |

Series de factura:
- **Serie C** — Clases mensuales. Generación masiva el día 1 de cada mes. Sin IVA ni IRPF.
- **Serie B** — Bolos (conciertos). Creación manual con IVA 10% e IRPF 15%.

La numeración es correlativa por serie y año (ej: `001C/2026`, `002C/2026`). La generación masiva calcula el último número antes del bucle y lo incrementa dentro de una transacción, garantizando secuencialidad y atomicidad.

---

## Modelo de datos

### Cliente

| Campo | Tipo | Descripción |
|-------|------|-------------|
| nombre | string | Nombre completo o razón social |
| nif_cif | string | DNI / CIF |
| email | string | |
| telefono | string | |
| direccion | string | Dirección fiscal |
| codigo_postal | string | |
| localidad | string | |
| provincia | string | |
| tipo | enum | `alumno` o `bolo` |
| activo | boolean | Solo alumnos. `true` = se incluye en la facturación masiva mensual |
| curso | string | Grupo/nivel, solo para alumnos |
| cuota_mensual | decimal | Cuota mensual fija. Solo para alumnos. |

### Factura

| Campo | Tipo | Descripción |
|-------|------|-------------|
| codigo | *virtual* | Generado: `001C/2026`. No se almacena en BD |
| serie | char(1) | `C` (clases) o `B` (bolos) |
| numero | integer | Correlativo por serie y año |
| cliente_id | foreign key | Relación con Cliente |
| concepto | text | Descripción del servicio |
| fecha_emision | date | |
| fecha_evento | date | Solo para bolos |
| subtotal | decimal | Base imponible |
| iva_porcentaje | decimal | |
| iva_monto | decimal | |
| irpf_porcentaje | decimal | |
| irpf_monto | decimal | |
| monto | decimal | Total líquido a percibir |
| estado | enum | `pendiente` o `pagada` |
