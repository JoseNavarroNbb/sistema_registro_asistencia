# Guía 02 — Crear el proyecto Laravel 10 en Windows

> **Sistema de Registro de Asistencia**  
> En esta guía vamos a crear el proyecto Laravel 10 directamente en Windows usando el PHP de XAMPP y Node.js. El proyecto corre en tu máquina y se conecta al contenedor MySQL que levantamos en la Guía 01.

---

## ¿Cómo funciona este enfoque?

No usamos Docker para la aplicación. El proyecto vive en tu máquina como cualquier proyecto local, y lo levantas con los comandos de siempre. Solo la base de datos corre en Docker.

```
Tu máquina Windows
│
├── Laravel  =  php artisan serve  = http://localhost:8000
├── Vite     =  npm run dev        = http://localhost:5173
│
└── Se conecta a MySQL en Docker   =  localhost:3307
```

---

## Prerequisitos

Antes de continuar verifica que tienes:

- XAMPP instalado con PHP 8.2 o superior
- Composer instalado
- Node.js instalado
- El contenedor `mysql-dev` corriendo (Guía 01 completada)

### Verificar las versiones desde la terminal

Abre una terminal (CMD o PowerShell) y ejecuta:

```bash
php -v
# PHP 8.2.x (cli)

composer -v
# Composer version 2.x.x

node -v
# v18.x.x o superior

npm -v
# 9.x.x o superior
```

> **¿`php` no se reconoce como comando?**  
> XAMPP no agrega PHP al PATH de Windows automáticamente. Ve a **Panel de Control / Sistema / Variables de entorno / Path** y agrega `C:\xampp\php`. Luego cierra y vuelve a abrir la terminal.

---

## Paso 1 — Crear el proyecto Laravel 10

Navega a la carpeta donde quieres guardar tus proyectos y ejecuta:

```bash
cd C:\dev\proyectos

composer create-project laravel/laravel:^10.0 sistema-asistencia
```

Esto descarga Laravel 10 y todas sus dependencias. Puede tardar unos minutos dependiendo de tu conexión. Cuando termine, entra a la carpeta del proyecto:

```bash
cd sistema-asistencia
```

---

## Paso 2 — Configurar el archivo `.env`

Laravel incluye un archivo `.env.example` con todas las variables de entorno. Cópialo:

```bash
copy .env.example .env
```

Abre el `.env` con tu editor y configura el bloque de base de datos así:

```env
APP_NAME="Sistema Asistencia"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1      # máquina local, donde está corriendo Docker
DB_PORT=3307           # Puerto externo del contenedor MySQL
DB_DATABASE=asistencia_db
DB_USERNAME=asistencia_user
DB_PASSWORD=asistencia_pass

VITE_API_URL=http://localhost:8000/api
```

> **¿Por qué aquí sí usamos `127.0.0.1` y el puerto `3307`?**  
> Cuando Laravel corre dentro de Docker se comunica con MySQL por la red interna usando el nombre del contenedor y el puerto `3306`. Pero aquí Laravel corre directamente la máquina Windows, así que tiene que llegar al contenedor por el puerto que Docker expuso hacia afuera, que es el `3307`. El `3306` interno no es accesible desde fuera de Docker.

---

## Paso 3 — Generar la clave de la aplicación

Laravel necesita una clave única para encriptar sesiones y cookies:

```bash
php artisan key:generate
```

Verifica que en tu `.env` ahora aparezca algo así:

```env
APP_KEY=base64:xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx=
```

---

## Paso 4 — Verificar la conexión a la base de datos

Antes de continuar, confirma que Laravel puede conectarse al contenedor MySQL:

```bash
php artisan tinker
```

Dentro de Tinker escribe:

```php
DB::connection()->getPdo();
```

Si ves un objeto `PDO` la conexión es exitosa. Si ves un error revisa que el contenedor `mysql-dev` esté corriendo con `docker ps`, que el puerto en `.env` sea `3307` y que el host sea `127.0.0.1`.

Escribe `exit` para salir de Tinker.

---

## Paso 5 — Instalar dependencias de Node

```bash
npm install
```

Esto instala Vite y todas las dependencias del frontend definidas en `package.json`.

---

## Paso 6 — Configurar Vite

Primero instala el plugin de Vue:

```bash
npm install --save-dev @vitejs/plugin-vue
```

Luego edita `vite.config.js` en la raíz del proyecto y déjalo así:

```javascript
import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/scss/main.scss', 'resources/js/app.js'],
            refresh: true,
        }),
        vue(),
    ],
    resolve: {
        alias: {
            '@': '/resources/js',
        },
    },
})
```

---

## Paso 7 — Levantar el proyecto

Necesitas dos terminales abiertas al mismo tiempo, ambas dentro de la carpeta del proyecto.

**Terminal 1 — Servidor Laravel:**

```bash
php artisan serve
```

Verás algo así:

```
INFO  Server running on [http://127.0.0.1:8000].
```

**Terminal 2 — Servidor Vite:**

```bash
npm run dev
```

Verás algo así:

```
VITE v5.x.x  ready in xxx ms

 Local:   http://localhost:5173/
```

Abre tu navegador en `http://localhost:8000` y deberías ver la página de bienvenida de Laravel. 

---

## Paso 8 — Ejecutar los scripts SQL

Con la conexión verificada, ejecuta los scripts para crear las tablas y datos iniciales. Puedes hacerlo desde **DBeaver** abriendo cada archivo y ejecutándolo, o desde la terminal:

```bash
docker exec -i mysql-dev mysql -u asistencia_user -pasistencia_pass asistencia_db < database/sql/02_crear_tablas.sql
docker exec -i mysql-dev mysql -u asistencia_user -pasistencia_pass asistencia_db < database/sql/03_relaciones_y_llaves.sql
docker exec -i mysql-dev mysql -u asistencia_user -pasistencia_pass asistencia_db < database/sql/04_datos_iniciales.sql
```

---

## Solución de problemas frecuentes en Windows

**`php` no se reconoce como comando**  
Agrega `C:\xampp\php` al PATH de Windows y reinicia la terminal.

**`SQLSTATE[HY000] [2002] No connection could be made`**  
Verifica que el contenedor MySQL esté corriendo con `docker ps`. También prueba cambiando `DB_HOST=localhost` por `DB_HOST=127.0.0.1` en el `.env`, en Windows pueden comportarse diferente.

**`SQLSTATE[HY000] [1045] Access denied for user`**  
Las credenciales no coinciden. Verifica que el usuario `asistencia_user` fue creado correctamente en el contenedor (Guía 01, Paso 6).

**Vite no recarga los cambios automáticamente**  
Agrega esta opción al `vite.config.js`:

```javascript
server: {
    watch: {
        usePolling: true,   // Necesario en Windows con algunos editores
    }
}
```

**Puerto 8000 ya en uso**  
Cambia el puerto de Laravel al levantarlo:

```bash
php artisan serve --port=8001
```

---

## Comandos del día a día

```bash
# Levantar el servidor Laravel
php artisan serve

# Levantar Vite
npm run dev

# Limpiar caché de configuración (útil al cambiar el .env)
php artisan config:clear

# Limpiar caché de rutas
php artisan route:clear

# Ver todas las rutas registradas
php artisan route:list

# Consola interactiva de Laravel
php artisan tinker
```

---

## Checklist de esta guía

- [x] PHP de XAMPP disponible en el PATH de Windows
- [x] Proyecto Laravel 10 creado con Composer
- [x] Archivo `.env` configurado con `DB_HOST=127.0.0.1` y `DB_PORT=3307`
- [x] `APP_KEY` generado correctamente
- [x] Conexión a MySQL verificada con Tinker
- [x] Dependencias de Node instaladas
- [x] Vite configurado con el plugin de Vue
- [x] Laravel respondiendo en `http://localhost:8000`
- [x] Scripts SQL ejecutados correctamente

---

**Guía anterior:** [01 — Contenedor MySQL compartido para desarrollo](./01-configuracion-docker-mysql.md)
