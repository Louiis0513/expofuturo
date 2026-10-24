# ExpoFuturo

Catálogo de universidades y carreras universitarias en Colombia (PHP + MySQL) con panel de administración. Identidad visual azul + lila + rosado.

## Requisitos

- XAMPP (Apache + MySQL)
- PHP 8+

## Instalación

1. Coloca el proyecto en `htdocs/ExpoFuturo` (ya está en esa ruta).
2. Asegúrate de que MySQL de XAMPP esté encendido.
3. Importa la base de datos:

```bash
/Applications/XAMPP/xamppfiles/bin/mysql -u root < bd/expofuturo.sql
```

También puedes importar `bd/expofuturo.sql` desde phpMyAdmin.

4. Abre el sitio:

**http://localhost/ExpoFuturo/**

## Acceso administrador

- URL: http://localhost/ExpoFuturo/admin/login.php
- Correo: `admin@demo.com`
- Contraseña: `Admin123!`

## Estructura

- `index.php` — inicio con 6 carreras destacadas (sin filtros)
- `buscar.php` — búsqueda pública con filtros, chips y paginación
- `carrera.php` — detalle con hero, universidad y relacionadas
- `admin/` — login, dashboard y CRUD con filtros y paginación (tablas en desktop, cards en móvil)
- `bd/expofuturo.sql` — esquema y seed colombiano (12 universidades, 30 carreras)
- `includes/` — conexión, helpers e imágenes por área
- `css/estilos.css` — tipografía Libre Baskerville + Figtree, azul/lila/rosado
- `img/` — logo + fotos de campus, estudiantes y áreas (Unsplash)

## Conexión

`includes/conexion.php` usa:

- Host: `127.0.0.1`
- Usuario: `root`
- Contraseña: (vacía)
- Base de datos: `expofuturo`
- Puerto: `3306`
- Charset: `utf8mb4`
