# Guía 01 — Contenedor MySQL compartido para desarrollo

> **Herramientas de Desarrollo — `~/dev/herramientas/mysql-dev/`**  
> Este contenedor MySQL **no pertenece a ningún proyecto en particular**. Es una herramienta compartida de tu entorno local que estará disponible para todos tus proyectos en desarrollo.

---

Cuando trabajas en varios proyectos al mismo tiempo, levantar un contenedor MySQL por cada uno genera problemas:

- Conflictos de puertos entre proyectos
- Recursos duplicados (memoria, disco)

La solución es tener **un solo servidor MySQL corriendo en tu máquina** que funcione igual que si lo tuvieras instalado de forma nativa, pero con todas las ventajas de Docker. Cada proyecto simplemente crea su propia base de datos dentro de ese servidor.

```
~/dev/herramientas/mysql-dev/  ==  Vive aquí, independiente de todo
       -> 
  Contenedor MySQL compartido (puerto 3307)
       ->
  ┌─────────────────┬──────────────────┬─────────────────┐
  │  asistencia_db  │   otro_proyecto  │  proyecto_tres  │
  └─────────────────┴──────────────────┴─────────────────┘
```

---

## Estructura de archivos

Crea esta carpeta y archivos en tu máquina, **fuera de cualquier proyecto**:

```
~/dev/herramientas/mysql-dev/
├── Dockerfile                  = Imagen personalizada de MySQL
├── configuracion.cnf           = Configuración del servidor MySQL
└── docker-compose.yml          = Levanta el contenedor compartido
```

---

## Paso 1 — Crear el Dockerfile

Este archivo construye la imagen de MySQL con nuestra configuración personalizada. Crea el archivo `~/dev/herramientas/mysql-dev/Dockerfile`:

```dockerfile
FROM mysql:8.0

LABEL maintainer="Tu Nombre <tu@correo.com>"
LABEL description="Contenedor MySQL compartido para desarrollo local"

# Copiamos nuestra configuración personalizada
COPY configuracion.cnf /etc/mysql/conf.d/

EXPOSE 3306
```

---

## Paso 2 — Crear la configuración del servidor MySQL

Este archivo define el comportamiento del servidor. Crea `~/dev/herramientas/mysql-dev/configuracion.cnf`:

```ini
[mysqld]
character-set-server=utf8mb4
collation-server=utf8mb4_unicode_ci
default-authentication-plugin=mysql_native_password

[client]
default-character-set=utf8mb4
```

**¿Qué hace cada directiva?**

| Directiva | Descripción |
|---|---|
| `character-set-server=utf8mb4` | UTF-8 completo — soporta español, emojis y caracteres especiales |
| `collation-server=utf8mb4_unicode_ci` | Ordenamiento insensible a mayúsculas y acentos |
| `default-authentication-plugin=mysql_native_password` | Compatibilidad con Laravel, DBeaver y clientes MySQL estándar |
| `[client] default-character-set=utf8mb4` | El cliente también usa UTF-8 al conectarse |

---

## Paso 3 — Crear el docker-compose.yml

Este es el archivo central que levanta el contenedor. Crea `~/dev/herramientas/mysql-dev/docker-compose.yml`:

```yaml
version: '3.9'

networks:
  red-dev:
    name: red-dev          # Red con nombre fijo para que los proyectos puedan encontrarla
    driver: bridge

volumes:
  datos-mysql-dev:
    driver: local

services:

  mysql-dev:
    build:
      context: .
      dockerfile: Dockerfile
    container_name: mysql-dev
    restart: unless-stopped   # Se reinicia automáticamente si Docker se reinicia
    ports:
      - "3307:3306"           # Puerto 3307 en tu máquina 3306 interno
    volumes:
      - datos-mysql-dev:/var/lib/mysql
    environment:
      MYSQL_ROOT_PASSWORD: root_dev_2024   # Contraseña del usuario root
    networks:
      - red-dev
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost", "-u", "root", "-proot_dev_2024"]
      interval: 10s
      timeout: 5s
      retries: 5
```

**Puntos clave de esta configuración:**

- **`restart: unless-stopped`** — El contenedor arranca automáticamente cada vez que inicias Docker Desktop, sin que tengas que levantarlo manualmente.
- **`MYSQL_ROOT_PASSWORD`** — Solo definimos root. Los usuarios por proyecto los crearemos manualmente en el Paso 6.
- **`red-dev`** — Red con nombre fijo (`name: red-dev`). Los proyectos se conectarán a esta red declarándola como externa.
- **Puerto `3307`** — Evitamos el `3306` por si tienes MySQL instalado nativamente en tu máquina.

---

## Paso 4 — Levantar el contenedor

Desde la carpeta `~/dev/herramientas/mysql-dev/`, ejecuta:

```bash
docker compose up --build -d
```

Verifica que esté corriendo:

```bash
docker ps
```

Deberías ver:

```
CONTAINER ID   IMAGE         PORTS                    NAMES
a1b2c3d4e5f6   mysql-dev     0.0.0.0:3307->3306/tcp   mysql-dev
```

---

## Paso 5 — Conectar DBeaver al contenedor compartido

Esta será tu **única conexión MySQL en DBeaver** para todos tus proyectos.

1. Abre DBeaver y haz clic en **Nueva Conexión**
2. Selecciona **MySQL** → **Siguiente**
3. Ingresa estos datos:

| Campo | Valor |
|---|---|
| **Server Host** | `localhost` |
| **Port** | `3307` |
| **Username** | `root` |
| **Password** | `root_dev_2024` |

4. Haz clic en **Test Connection** → debería decir **Connected**
5. Haz clic en **Finalizar**

> Desde esta conexión podrás ver y administrar **todas las bases de datos** de todos tus proyectos en un solo lugar.

---

## Paso 6 — Crear una base de datos y usuario por proyecto

Cada vez que arranques un proyecto nuevo, sigue este proceso para crear su base de datos y un usuario con permisos restringidos. Los proyectos **nunca deben conectarse con root**.

### Acceder al servidor MySQL

```bash
docker exec -it mysql-dev mysql -u root -proot_dev_2024
```

### Crear la base de datos del proyecto

```sql
CREATE DATABASE asistencia_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
```

### Crear el usuario del aplicativo

Este usuario solo tendrá los permisos necesarios para que la aplicación funcione. **No podrá eliminar tablas ni administrar el servidor.**

```sql
CREATE USER 'asistencia_user'@'%' IDENTIFIED BY 'asistencia_pass';
```

> El `%` significa que este usuario puede conectarse desde **cualquier host**, lo que incluye otros contenedores Docker en la misma red.

### Otorgar permisos al usuario

```sql
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, INDEX, ALTER
  ON asistencia_db.*
  TO 'asistencia_user'@'%';

FLUSH PRIVILEGES;
```

**Lista de permisos que solo debe terner el usuario para el aplicativo**

| Permiso | ¿Para qué lo necesita la aplicación? |
|---|---|
| `SELECT` | Leer datos (consultas) |
| `INSERT` | Crear nuevos registros |
| `UPDATE` | Modificar registros existentes |
| `DELETE` | Eliminar registros |
| `CREATE` | Crear tablas (migraciones) |
| `INDEX` | Crear índices (migraciones) |
| `ALTER` | Modificar estructura de tablas (migraciones) |

> Permisos como `DROP`, `GRANT` o `SUPER` quedan excluidos intencionalmente. Si la aplicación tiene un bug o es comprometida, el daño queda limitado a su propia base de datos.

### Verificar que el usuario y permisos quedaron bien

```sql
SHOW GRANTS FOR 'asistencia_user'@'%';
```

Salir de MySQL:

```sql
EXIT;
```

---

## Paso 7 — Conectar los proyectos al contenedor compartido

```
En el `.env` del proyecto:

```env
DB_CONNECTION=mysql
DB_HOST=mysql-dev       #  Nombre del contenedor compartido
DB_PORT=3306            #  Puerto interno (no el 3307)
DB_DATABASE=asistencia_db
DB_USERNAME=asistencia_user
DB_PASSWORD=asistencia_pass
```

> **¿Por qué `DB_HOST=mysql-dev` y `DB_PORT=3306`?**  
> Dentro de la red Docker los contenedores se comunican por el **nombre del contenedor**, no por `localhost`. El puerto es el interno (`3306`), porque el `3307` solo existe para conexiones desde tu máquina (DBeaver, terminal local, etc.).

---

## Comandos útiles del día a día

```bash
# Levantar el contenedor MySQL compartido
cd ~/dev/herramientas/mysql-dev
docker compose up -d

# Verificar que está corriendo
docker ps | grep mysql-dev

# Ver los logs del servidor MySQL
docker compose logs -f mysql-dev

# Acceder a MySQL como root
docker exec -it mysql-dev mysql -u root -proot_dev_2024

# Ver todas las bases de datos existentes
docker exec -it mysql-dev mysql -u root -proot_dev_2024 -e "SHOW DATABASES;"

# Ver todos los usuarios creados
docker exec -it mysql-dev mysql -u root -proot_dev_2024 -e "SELECT user, host FROM mysql.user;"

# Detener el contenedor (los datos se conservan)
docker compose stop

# Eliminar el contenedor pero conservar los datos
docker compose down

# Eliminar TODO incluyendo los datos de todas las bases de datos
docker compose down -v
```

---

## Resumen — Flujo para cada proyecto nuevo

```
1. El contenedor mysql-dev ya está corriendo        
2. Acceder a MySQL como root
3. CREATE DATABASE nombre_proyecto_db
4. CREATE USER 'nombre_user'@'%' IDENTIFIED BY 'password'
5. GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, INDEX, ALTER
     ON nombre_proyecto_db.* TO 'nombre_user'@'%'
6. En docker-compose.yml del proyecto → red-dev como external: true
7. En .env del proyecto → DB_HOST=mysql-dev, DB_PORT=3306
```

---

## Checklist de esta guía

- [x] Carpeta `~/dev/herramientas/mysql-dev/` creada
- [x] Archivos `Dockerfile`, `configuracion.cnf` y `docker-compose.yml` creados
- [x] Contenedor `mysql-dev` corriendo en el puerto `3307`
- [x] Conexión exitosa desde DBeaver con usuario `root`
- [x] Base de datos `asistencia_db` creada
- [x] Usuario `asistencia_user` creado con permisos restringidos
- [x] Proyecto conectado a la red `red-dev` como externa

---

**Siguiente guía:** [02 — Crear el proyecto Laravel 10](./02-crear-proyecto-laravel.md)
