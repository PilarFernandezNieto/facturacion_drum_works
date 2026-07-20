# Manual: nueva instancia de DrummerWorks para otro autónomo

Este documento describe cómo crear una **segunda instancia independiente** de la
aplicación (mismo código, distinta base de datos y distinto dominio) para dar
servicio a un segundo músico/profesor autónomo, sin mezclar sus datos con los
de la instancia original.

> **Por qué instancias separadas y no multiusuario compartido**: hoy el
> backend (`ClienteController`, `FacturaController`) no filtra ningún dato por
> usuario — cualquier cuenta autenticada ve todos los clientes y facturas.
> Convertir esto en multi-tenant real exigiría tocar modelos, controladores,
> el middleware de PDFs (`QueryTokenMiddleware`) y añadir tests de
> aislamiento. Duplicar el despliegue es mucho más rápido, más seguro (cero
> riesgo de fuga de datos entre autónomos) y respeta que cada uno lleva su
> propia numeración correlativa de facturas de cara a Hacienda.

---

## 0. Qué necesitas antes de empezar

- Acceso al hosting (Apache/cPanel, a juzgar por los `.htaccess` del proyecto)
  o al servidor donde se desplegará la nueva instancia.
- Un dominio o subdominio nuevo para el frontend (ej. `appnombre2.es`).
- Un dominio o subdominio nuevo para el backend (ej. `api.appnombre2.es`),
  siguiendo el mismo patrón que la instancia actual (`api.drummerworksacademy.es`).
- Una base de datos MySQL 8 nueva y vacía, con su usuario y contraseña.
- PHP 8.4, Composer y Node.js instalados en el entorno de build/despliegue.
- El logo (si tiene uno gráfico) y los colores de marca del segundo autónomo.

---

## 1. DNS y Virtual Hosts (VPS con Apache)

Al ser un VPS (no cPanel), los dominios/subdominios no se dan de alta solos:
hay que apuntar el DNS y crear un `VirtualHost` de Apache para cada uno.

### 1.1 DNS

En el proveedor de dominio del segundo autónomo, crea registros `A` (o
`CNAME` si usas subdominio de un dominio ya apuntado) hacia la IP del VPS:

```
appnombre2.es        A     <IP_DEL_VPS>
api.appnombre2.es     A     <IP_DEL_VPS>
```

Espera a que propague (unos minutos, hasta 24h) antes de pedir el
certificado TLS.

### 1.2 Directorios en el servidor

```bash
sudo mkdir -p /var/www/drummerworks-autonomo2
sudo chown -R $USER:www-data /var/www/drummerworks-autonomo2
```

### 1.3 VirtualHost del frontend

`/etc/apache2/sites-available/appnombre2.es.conf`:

```apache
<VirtualHost *:80>
    ServerName appnombre2.es
    DocumentRoot /var/www/drummerworks-autonomo2/client/dist

    <Directory /var/www/drummerworks-autonomo2/client/dist>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/appnombre2_error.log
    CustomLog ${APACHE_LOG_DIR}/appnombre2_access.log combined
</VirtualHost>
```

### 1.4 VirtualHost del backend

`/etc/apache2/sites-available/api.appnombre2.es.conf`:

```apache
<VirtualHost *:80>
    ServerName api.appnombre2.es
    DocumentRoot /var/www/drummerworks-autonomo2/api/public

    <Directory /var/www/drummerworks-autonomo2/api/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/api_appnombre2_error.log
    CustomLog ${APACHE_LOG_DIR}/api_appnombre2_access.log combined
</VirtualHost>
```

> Importante: el `DocumentRoot` del backend apunta a `api/public`, **no** a
> `api/` — Laravel nunca debe servirse desde la raíz del proyecto.

### 1.5 Habilitar sitios y módulos

```bash
sudo a2ensite appnombre2.es.conf
sudo a2ensite api.appnombre2.es.conf
sudo a2enmod rewrite headers
sudo systemctl reload apache2
```

`mod_rewrite` es imprescindible: tanto `api/public/.htaccess` (rutas de
Laravel) como `client/dist/.htaccess` (rutas de Vue Router) dependen de él.

### 1.6 HTTPS con Let's Encrypt

```bash
sudo apt install certbot python3-certbot-apache   # si no está instalado
sudo certbot --apache -d appnombre2.es
sudo certbot --apache -d api.appnombre2.es
```

Certbot reescribe los `VirtualHost` para añadir el bloque `:443` y renueva
automáticamente vía cron/systemd timer.

### 1.7 Verificar

```bash
sudo apache2ctl configtest   # debe decir "Syntax OK"
curl -I http://appnombre2.es
curl -I http://api.appnombre2.es
```

---

## 2. Clonar el repositorio

```bash
cd /var/www/drummerworks-autonomo2
git clone <url-del-repositorio> .
```

Si vas a mantener ambas instancias en el mismo servidor, usa una carpeta
distinta para cada una (`drummerworks-autonomo1/`, `drummerworks-autonomo2/`),
tal como se apuntó en los `VirtualHost` del paso 1.

> No conectes este clon como remoto del repo original si vais a divergir en
> contenido (logo/colores): trabaja sobre una copia local o un fork, no hagas
> push de las personalizaciones al repo compartido a menos que sea deseado.

---

## 3. Backend (`api/`)

### 3.1 Instalar dependencias

```bash
cd api
composer install --no-dev --optimize-autoloader
```

### 3.2 Configurar `.env`

```bash
cp .env.example .env
php artisan key:generate
```

Edita `api/.env` con los datos de la **nueva** instancia:

```env
APP_NAME="NombreAutonomo2"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.appnombre2.es

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=drummerworks_autonomo2
DB_USERNAME=<usuario_bd>
DB_PASSWORD=<password_bd>
```

### 3.3 CORS: añadir el dominio del frontend

`api/config/cors.php` solo permite `http://localhost:5173` por defecto.
Debes añadir el dominio de producción del **nuevo** frontend o las peticiones
del navegador serán bloqueadas:

```php
'allowed_origins' => ['https://appnombre2.es'],
```

(Si la instancia original ya tenía el suyo añadido en este mismo archivo,
recuerda que aquí es un clon independiente — solo debe llevar el dominio de
esta instancia, no ambos.)

### 3.4 Migraciones

```bash
php artisan migrate --force
```

### 3.5 Storage y permisos

```bash
php artisan storage:link
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

El código ya está en `/var/www/drummerworks-autonomo2/api`, que es el
`DocumentRoot` (vía `api/public`) configurado en el `VirtualHost` del paso 1 —
no hace falta "subirlo" a otro sitio.

### 3.6 Crear el primer usuario

Con la API ya desplegada y accesible, registra al autónomo desde el propio
frontend (`/registro`) o vía `tinker`:

```bash
php artisan tinker
>>> \App\Models\User::factory()->create(['name' => 'Nombre', 'email' => 'correo@dominio.es', 'password' => bcrypt('contraseña-segura')]);
```

---

## 4. Frontend (`client/`)

### 4.1 Instalar dependencias

```bash
cd client
npm install
```

### 4.2 Configurar `.env.production`

Edita `client/.env.production`:

```env
VITE_APP_NAME="NombreAutonomo2"
VITE_APP_BACKEND_URL=https://api.appnombre2.es
```

### 4.3 Personalización visual

**Título de la pestaña / favicon** — `client/index.html`:

```html
<title>NombreAutonomo2</title>
```

Sustituye `client/public/favicon.ico` por el icono del nuevo autónomo
(mismo nombre de archivo, mismo formato).

**Colores de marca** — `client/src/assets/main.css`, bloque `@theme`:

```css
@theme {
  --color-principal: #TU_COLOR;
  --color-principal-50: #TU_COLOR_CLARO;
  --color-principal-100: #TU_COLOR_MEDIO;
  --color-principal-200: #TU_COLOR_INTENSO;
  --color-principal-hover: #TU_COLOR_HOVER;
}
```

Estas variables ya se usan en toda la UI (títulos, botones, sidebar), así
que cambiarlas aquí actualiza el tema completo sin tocar componentes.

**Logo gráfico** (si se añade uno): colocar el archivo en
`client/src/assets/` o `client/public/` y referenciarlo desde
`AppSidebar.vue` / `AppHeader.vue` donde corresponda.

### 4.4 Build de producción

```bash
npm run build
```

Esto genera `client/dist/`, que ya incluye el `.htaccess` con el rewrite
para el router de Vue. Este directorio ya es el que sirve el `VirtualHost`
del frontend configurado en el paso 1 — no requiere copia adicional si
compilaste directamente en `/var/www/drummerworks-autonomo2/client`.

---

## 5. Verificación post-despliegue (checklist)

- [ ] `apache2ctl configtest` sin errores y ambos sitios habilitados
      (`apache2ctl -S`).
- [ ] Certificados TLS activos y renovación automática configurada
      (`sudo certbot renew --dry-run`).
- [ ] `https://appnombre2.es` carga el login sin errores de consola.
- [ ] El registro/login funciona y devuelve token (revisar Network → sin
      errores CORS).
- [ ] Crear un cliente de tipo `alumno` y otro de tipo `bolo`.
- [ ] Generar una factura Serie B manual y comprobar IVA/IRPF calculados.
- [ ] Ejecutar "Generar Clases (Mes)" y comprobar que la numeración Serie C
      arranca en `001C/<año>` (base de datos nueva, sin histórico).
- [ ] Descargar el PDF de una factura y comprobar que el código y los datos
      del autónomo (no los del otro) aparecen correctos.
- [ ] Confirmar que esta instancia **no** comparte base de datos ni dominio
      con la primera (aislamiento real).

---

## 6. Resumen de comandos

```bash
# --- DNS ---
# Crear registros A de appnombre2.es y api.appnombre2.es -> IP del VPS

# --- Directorio y VirtualHosts ---
sudo mkdir -p /var/www/drummerworks-autonomo2
sudo chown -R $USER:www-data /var/www/drummerworks-autonomo2
# crear /etc/apache2/sites-available/appnombre2.es.conf
# crear /etc/apache2/sites-available/api.appnombre2.es.conf
sudo a2ensite appnombre2.es.conf
sudo a2ensite api.appnombre2.es.conf
sudo a2enmod rewrite headers
sudo systemctl reload apache2
sudo certbot --apache -d appnombre2.es
sudo certbot --apache -d api.appnombre2.es

# --- Clonar ---
cd /var/www/drummerworks-autonomo2
git clone <url-repo> .

# --- Backend ---
cd api
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
# editar .env: APP_URL, DB_*
# editar config/cors.php: allowed_origins
php artisan migrate --force
php artisan storage:link
sudo chown -R www-data:www-data storage bootstrap/cache

# --- Frontend ---
cd ../client
npm install
# editar .env.production: VITE_APP_NAME, VITE_APP_BACKEND_URL
# editar index.html (title), assets/main.css (colores), favicon.ico (logo)
npm run build
```

---

## 7. Mantenimiento futuro

Si en el futuro se corrigen bugs o se añaden funcionalidades al código base,
esos cambios deben aplicarse **manualmente a cada instancia** (no hay
sincronización automática entre clones). Si esto se vuelve tedioso con más
de 2-3 autónomos, seria el momento de reconsiderar la migración a un modelo
multiusuario real con aislamiento por `user_id` — pero para 2 instancias no
compensa la complejidad.
