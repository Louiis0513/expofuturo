<?php
require_once __DIR__ . '/cache.php';

function e($texto) {
    return htmlspecialchars((string) $texto, ENT_QUOTES, "UTF-8");
}

function adminLogueado() {
    return isset($_SESSION["admin"]);
}

function requiereAdmin() {
    if (!adminLogueado()) {
        header("Location: login.php");
        exit;
    }
}

function guardarMensaje($texto) {
    $_SESSION["mensaje"] = $texto;
}

function obtenerMensaje() {
    $mensaje = $_SESSION["mensaje"] ?? "";
    unset($_SESSION["mensaje"]);
    return $mensaje;
}

/**
 * Imagen local según área de conocimiento.
 * $prefijo: "" desde raíz, "../" desde admin.
 */
function imagenArea($nombreArea, $prefijo = "") {
    $mapa = [
        "Ingeniería" => "area-ingenieria.jpg",
        "Ciencias de la Salud" => "area-salud.jpg",
        "Ciencias Sociales y Humanidades" => "area-sociales.jpg",
        "Ciencias Sociales" => "area-sociales.jpg",
        "Artes, Diseño y Comunicación" => "area-artes.jpg",
        "Artes y Diseño" => "area-artes.jpg",
        "Administración y Negocios" => "area-negocios.jpg",
        "Ciencias Exactas y Naturales" => "area-exactas.jpg",
        "Ciencias Exactas" => "area-exactas.jpg",
        "Derecho y Ciencias Políticas" => "area-sociales.jpg",
    ];
    $archivo = $mapa[$nombreArea] ?? "campus-biblioteca.jpg";
    return $prefijo . "img/" . $archivo;
}

function etiquetaTipo($tipo) {
    return $tipo === "publica" ? "Pública" : "Privada";
}

/**
 * Calcula página actual, offset y total de páginas.
 *
 * @return array{pagina:int, por_pagina:int, offset:int, total:int, total_paginas:int, desde:int, hasta:int}
 */
function calcularPaginacion(int $total, int $porPagina = 10, string $param = "p"): array {
    $porPagina = max(1, $porPagina);
    $totalPaginas = max(1, (int) ceil($total / $porPagina));
    $pagina = max(1, (int) ($_GET[$param] ?? 1));
    if ($pagina > $totalPaginas) {
        $pagina = $totalPaginas;
    }
    $offset = ($pagina - 1) * $porPagina;
    $desde = $total === 0 ? 0 : $offset + 1;
    $hasta = min($offset + $porPagina, $total);

    return [
        "pagina" => $pagina,
        "por_pagina" => $porPagina,
        "offset" => $offset,
        "total" => $total,
        "total_paginas" => $totalPaginas,
        "desde" => $desde,
        "hasta" => $hasta,
    ];
}

/**
 * Construye URL conservando filtros GET y cambiando la página.
 */
function urlConPagina(string $base, array $params, int $pagina, string $param = "p"): string {
    $params[$param] = $pagina;
    $params = array_filter($params, static function ($v) {
        return $v !== "" && $v !== null && $v !== false;
    });
    $qs = http_build_query($params);
    return $base . ($qs !== "" ? "?" . $qs : "");
}

/**
 * Renderiza controles de paginación (Anterior / números / Siguiente).
 */
function renderPaginacion(array $pag, string $base, array $params = [], string $param = "p"): string {
    if ($pag["total"] === 0 || $pag["total_paginas"] <= 1) {
        return "";
    }

    $actual = $pag["pagina"];
    $totalPaginas = $pag["total_paginas"];
    $html = '<nav class="paginacion" aria-label="Paginación">';

    if ($actual > 1) {
        $html .= '<a class="paginacion-btn" href="' . e(urlConPagina($base, $params, $actual - 1, $param)) . '" aria-label="Página anterior">← Anterior</a>';
    } else {
        $html .= '<span class="paginacion-btn deshabilitado" aria-disabled="true">← Anterior</span>';
    }

    $html .= '<div class="paginacion-nums" role="list">';
    $ventana = 2;
    $inicio = max(1, $actual - $ventana);
    $fin = min($totalPaginas, $actual + $ventana);

    if ($inicio > 1) {
        $html .= '<a role="listitem" href="' . e(urlConPagina($base, $params, 1, $param)) . '">1</a>';
        if ($inicio > 2) {
            $html .= '<span class="paginacion-ellipsis" aria-hidden="true">…</span>';
        }
    }

    for ($i = $inicio; $i <= $fin; $i++) {
        if ($i === $actual) {
            $html .= '<span class="activo" role="listitem" aria-current="page">' . $i . '</span>';
        } else {
            $html .= '<a role="listitem" href="' . e(urlConPagina($base, $params, $i, $param)) . '">' . $i . '</a>';
        }
    }

    if ($fin < $totalPaginas) {
        if ($fin < $totalPaginas - 1) {
            $html .= '<span class="paginacion-ellipsis" aria-hidden="true">…</span>';
        }
        $html .= '<a role="listitem" href="' . e(urlConPagina($base, $params, $totalPaginas, $param)) . '">' . $totalPaginas . '</a>';
    }

    $html .= '</div>';

    if ($actual < $totalPaginas) {
        $html .= '<a class="paginacion-btn" href="' . e(urlConPagina($base, $params, $actual + 1, $param)) . '" aria-label="Página siguiente">Siguiente →</a>';
    } else {
        $html .= '<span class="paginacion-btn deshabilitado" aria-disabled="true">Siguiente →</span>';
    }

    $html .= '</nav>';
    return $html;
}

function cssVersion(): string
{
    $path = __DIR__ . '/../css/estilos.css';
    return is_file($path) ? (string) filemtime($path) : '1';
}

function enviarHeadersCache(string $modo = 'privado'): void
{
    if (headers_sent()) {
        return;
    }
    if ($modo === 'publico') {
        // HTML breve; la fuente de verdad del catálogo es el caché de archivos (TTL 4 h + parche al editar).
        header('Cache-Control: public, max-age=120, stale-while-revalidate=60');
        header('Vary: Cookie');
    } else {
        header('Cache-Control: no-store, private');
    }
}

/* ——— Lecturas cacheadas (hosting compartido) ——— */

function obtenerAreas(mysqli $db): array
{
    $cached = cacheGet('areas');
    if (is_array($cached)) {
        return $cached;
    }
    $rows = $db->query('SELECT id_area, nombre FROM area ORDER BY nombre')->fetch_all(MYSQLI_ASSOC);
    cacheSet('areas', $rows, CACHE_TTL_AREAS);
    return $rows;
}

/** @return list<string> */
function obtenerCiudades(mysqli $db): array
{
    $cached = cacheGet('ciudades');
    if (is_array($cached)) {
        return $cached;
    }
    $rows = $db->query('SELECT DISTINCT ciudad FROM universidad ORDER BY ciudad')->fetch_all(MYSQLI_ASSOC);
    $ciudades = array_column($rows, 'ciudad');
    cacheSet('ciudades', $ciudades, CACHE_TTL_DEFAULT);
    return $ciudades;
}

/**
 * @return array{universidades:int,carreras:int}
 */
function obtenerStats(mysqli $db): array
{
    $cached = cacheGet('stats');
    if (is_array($cached) && isset($cached['universidades'], $cached['carreras'])) {
        return $cached;
    }
    $stats = [
        'universidades' => (int) $db->query('SELECT COUNT(*) AS c FROM universidad')->fetch_assoc()['c'],
        'carreras' => (int) $db->query('SELECT COUNT(*) AS c FROM carrera')->fetch_assoc()['c'],
    ];
    cacheSet('stats', $stats, CACHE_TTL_DEFAULT);
    return $stats;
}

/**
 * Seis carreras del home.
 * @return list<array<string,mixed>>
 */
function obtenerHomeDestacadas(mysqli $db): array
{
    $cached = cacheGet('home_destacadas');
    if (is_array($cached)) {
        return $cached;
    }
    $rows = $db->query(
        "SELECT c.id_carrera, c.nombre AS carrera, c.duracion_semestres, c.modalidad, c.descripcion,
                a.nombre AS area, u.nombre AS universidad, u.ciudad, u.tipo
         FROM carrera c
         INNER JOIN area a ON a.id_area = c.id_area
         INNER JOIN universidad u ON u.id_universidad = c.id_universidad
         ORDER BY c.nombre ASC
         LIMIT 6"
    )->fetch_all(MYSQLI_ASSOC);
    cacheSet('home_destacadas', $rows, CACHE_TTL_DEFAULT);
    return $rows;
}

/**
 * Catálogo público. Cachea sin búsqueda de texto (área ± ciudad ± tipo).
 * Devuelve la lista completa; la paginación se aplica en el caller.
 *
 * @return list<array<string,mixed>>
 */
function obtenerCatalogo(mysqli $db, int $idArea = 0, string $ciudad = '', string $tipo = '', string $q = ''): array
{
    $q = trim($q);
    $ciudad = trim($ciudad);
    $tipo = ($tipo === 'publica' || $tipo === 'privada') ? $tipo : '';
    $usarCache = ($q === '');
    $cacheKey = cacheCatalogoKey($idArea, $ciudad, $tipo);

    if ($usarCache) {
        $cached = cacheGet($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }
    }

    $sql = "SELECT c.id_carrera, c.nombre AS carrera, c.duracion_semestres, c.modalidad, c.descripcion,
                   c.id_area, c.id_universidad,
                   a.nombre AS area, u.nombre AS universidad, u.ciudad, u.tipo
            FROM carrera c
            INNER JOIN area a ON a.id_area = c.id_area
            INNER JOIN universidad u ON u.id_universidad = c.id_universidad
            WHERE 1=1";
    $types = '';
    $params = [];

    if ($idArea > 0) {
        $sql .= ' AND c.id_area = ?';
        $types .= 'i';
        $params[] = $idArea;
    }
    if ($ciudad !== '') {
        $sql .= ' AND u.ciudad = ?';
        $types .= 's';
        $params[] = $ciudad;
    }
    if ($tipo !== '') {
        $sql .= ' AND u.tipo = ?';
        $types .= 's';
        $params[] = $tipo;
    }
    if ($q !== '') {
        $sql .= ' AND (c.nombre LIKE ? OR c.descripcion LIKE ? OR u.nombre LIKE ?)';
        $types .= 'sss';
        $like = '%' . $q . '%';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }
    $sql .= ' ORDER BY c.nombre ASC';

    $stmt = $db->prepare($sql);
    if ($params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    if ($usarCache) {
        cacheSet($cacheKey, $rows, CACHE_TTL_DEFAULT);
        if ($ciudad !== '') {
            cacheRegistrarFiltroCiudad($ciudad);
        }
    }
    return $rows;
}

function obtenerCarrera(mysqli $db, int $id): ?array
{
    if ($id <= 0) {
        return null;
    }
    $key = 'carrera_' . $id;
    $cached = cacheGet($key);
    if (is_array($cached)) {
        return $cached;
    }
    if ($cached === false) {
        return null;
    }
    $stmt = $db->prepare(
        "SELECT c.id_carrera, c.nombre AS carrera, c.duracion_semestres, c.modalidad, c.descripcion AS desc_carrera,
                c.id_area, a.nombre AS area,
                u.id_universidad, u.nombre AS universidad, u.ciudad, u.tipo, u.sitio_web, u.descripcion AS desc_universidad
         FROM carrera c
         INNER JOIN area a ON a.id_area = c.id_area
         INNER JOIN universidad u ON u.id_universidad = c.id_universidad
         WHERE c.id_carrera = ?"
    );
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if ($row) {
        cacheSet($key, $row, CACHE_TTL_CARRERA);
        return $row;
    }
    cacheSet($key, false, CACHE_TTL_NEGATIVO);
    return null;
}

/**
 * Relacionadas del mismo área (no cachea: lista corta y barata).
 * @return list<array<string,mixed>>
 */
function obtenerCarrerasRelacionadas(mysqli $db, int $idArea, int $exceptoId, int $limit = 3): array
{
    if ($idArea <= 0) {
        return [];
    }
    $limit = max(1, min(20, $limit));
    $stmt = $db->prepare(
        "SELECT c.id_carrera, c.nombre AS carrera, u.nombre AS universidad, u.ciudad, a.nombre AS area
         FROM carrera c
         INNER JOIN universidad u ON u.id_universidad = c.id_universidad
         INNER JOIN area a ON a.id_area = c.id_area
         WHERE c.id_area = ? AND c.id_carrera <> ?
         ORDER BY c.nombre
         LIMIT {$limit}"
    );
    $stmt->bind_param('ii', $idArea, $exceptoId);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/* ——— Parche de caché (write-through): sin wipe total ——— */

/** Carga carrera + área + universidad para catálogo/ficha. */
function cargarCarreraCompleta(mysqli $db, int $id): ?array
{
    if ($id <= 0) {
        return null;
    }
    $stmt = $db->prepare(
        "SELECT c.id_carrera, c.nombre AS carrera, c.duracion_semestres, c.modalidad, c.descripcion,
                c.descripcion AS desc_carrera, c.id_area, c.id_universidad,
                a.nombre AS area,
                u.nombre AS universidad, u.ciudad, u.tipo, u.sitio_web, u.descripcion AS desc_universidad
         FROM carrera c
         INNER JOIN area a ON a.id_area = c.id_area
         INNER JOIN universidad u ON u.id_universidad = c.id_universidad
         WHERE c.id_carrera = ?"
    );
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row ?: null;
}

/** Fila reducida para listados del catálogo / home. */
function carreraFilaCatalogo(array $row): array
{
    return [
        'id_carrera' => (int) ($row['id_carrera'] ?? 0),
        'carrera' => (string) ($row['carrera'] ?? $row['nombre'] ?? ''),
        'duracion_semestres' => (int) ($row['duracion_semestres'] ?? 0),
        'modalidad' => (string) ($row['modalidad'] ?? ''),
        'descripcion' => (string) ($row['descripcion'] ?? $row['desc_carrera'] ?? ''),
        'id_area' => (int) ($row['id_area'] ?? 0),
        'id_universidad' => (int) ($row['id_universidad'] ?? 0),
        'area' => (string) ($row['area'] ?? ''),
        'universidad' => (string) ($row['universidad'] ?? ''),
        'ciudad' => (string) ($row['ciudad'] ?? ''),
        'tipo' => (string) ($row['tipo'] ?? ''),
    ];
}

function carreraEntraEnFiltroCatalogo(array $carrera, int $idArea, string $ciudad, string $tipo): bool
{
    if ($idArea > 0 && (int) ($carrera['id_area'] ?? 0) !== $idArea) {
        return false;
    }
    if ($ciudad !== '' && (string) ($carrera['ciudad'] ?? '') !== $ciudad) {
        return false;
    }
    if ($tipo !== '' && (string) ($carrera['tipo'] ?? '') !== $tipo) {
        return false;
    }
    return true;
}

/** Quita id de una lista de carreras cacheada. */
function cacheListaQuitarCarrera(array $lista, int $idCarrera): array
{
    $out = [];
    foreach ($lista as $row) {
        if (!is_array($row)) {
            continue;
        }
        if ((int) ($row['id_carrera'] ?? 0) === $idCarrera) {
            continue;
        }
        $out[] = $row;
    }
    return $out;
}

/** Inserta/reemplaza carrera en lista ordenada por nombre. */
function cacheListaUpsertCarrera(array $lista, array $carrera): array
{
    $id = (int) ($carrera['id_carrera'] ?? 0);
    $lista = cacheListaQuitarCarrera($lista, $id);
    $lista[] = carreraFilaCatalogo($carrera);
    usort($lista, static function (array $a, array $b): int {
        return strcasecmp((string) ($a['carrera'] ?? ''), (string) ($b['carrera'] ?? ''));
    });
    return $lista;
}

/**
 * Parsea catalogo_a{area}_c_{hash|all}_t_{tipo|all}.
 * @return array{0:int,1:string|null|false,2:string} [idArea, ciudad|null|false, tipo]
 */
function cacheParseCatalogoKey(string $key, array $ciudadesIdx): array
{
    if (!preg_match('/^catalogo_a(\d+)_c_([a-f0-9]{32}|all)_t_(all|publica|privada)$/', $key, $m)) {
        return [0, false, ''];
    }
    $idArea = (int) $m[1];
    $ciudad = null;
    if ($m[2] !== 'all') {
        if (!isset($ciudadesIdx[$m[2]])) {
            return [$idArea, false, $m[3] === 'all' ? '' : $m[3]];
        }
        $ciudad = (string) $ciudadesIdx[$m[2]];
    }
    $tipo = $m[3] === 'all' ? '' : $m[3];
    return [$idArea, $ciudad, $tipo];
}

function cacheParcheStats(mysqli $db): void
{
    $stats = [
        'universidades' => (int) $db->query('SELECT COUNT(*) AS c FROM universidad')->fetch_assoc()['c'],
        'carreras' => (int) $db->query('SELECT COUNT(*) AS c FROM carrera')->fetch_assoc()['c'],
    ];
    cachePatch('stats', $stats, CACHE_TTL_DEFAULT);
}

function cacheParcheHomeDestacadas(mysqli $db): void
{
    $peek = cachePeek('home_destacadas');
    if ($peek === null) {
        return;
    }
    $rows = $db->query(
        "SELECT c.id_carrera, c.nombre AS carrera, c.duracion_semestres, c.modalidad, c.descripcion,
                a.nombre AS area, u.nombre AS universidad, u.ciudad, u.tipo
         FROM carrera c
         INNER JOIN area a ON a.id_area = c.id_area
         INNER JOIN universidad u ON u.id_universidad = c.id_universidad
         ORDER BY c.nombre ASC
         LIMIT 6"
    )->fetch_all(MYSQLI_ASSOC);
    cachePatch('home_destacadas', $rows, CACHE_TTL_DEFAULT);
}

function cacheParcheCiudades(mysqli $db): void
{
    $rows = $db->query('SELECT DISTINCT ciudad FROM universidad ORDER BY ciudad')->fetch_all(MYSQLI_ASSOC);
    cachePatch('ciudades', array_column($rows, 'ciudad'), CACHE_TTL_DEFAULT);
}

/**
 * Parchea ficha + catálogos existentes + home + stats tras create/update de carrera.
 */
function cacheParcheCarrera(mysqli $db, int $idCarrera, ?array $antes = null): void
{
    $idCarrera = (int) $idCarrera;
    if ($idCarrera <= 0) {
        return;
    }
    $ahora = cargarCarreraCompleta($db, $idCarrera);

    if ($ahora) {
        cachePatch('carrera_' . $idCarrera, $ahora, CACHE_TTL_CARRERA);
    } else {
        cachePatch('carrera_' . $idCarrera, false, CACHE_TTL_NEGATIVO);
    }

    $ciudadesIdx = cacheFiltrosCiudadConocidos();
    foreach (cacheKeysByPrefix('catalogo_') as $key) {
        [$idArea, $ciudad, $tipo] = cacheParseCatalogoKey($key, $ciudadesIdx);
        if ($ciudad === false) {
            // Ciudad desconocida: quitar por id (seguro) y no reinsertar
            cachePatchIfPresent($key, static function ($data) use ($idCarrera) {
                return is_array($data) ? cacheListaQuitarCarrera($data, $idCarrera) : $data;
            });
            continue;
        }

        cachePatchIfPresent($key, static function ($data) use ($ahora, $idCarrera, $idArea, $ciudad, $tipo) {
            if (!is_array($data)) {
                return $data;
            }
            $data = cacheListaQuitarCarrera($data, $idCarrera);
            if ($ahora && carreraEntraEnFiltroCatalogo($ahora, $idArea, (string) ($ciudad ?? ''), $tipo)) {
                $data = cacheListaUpsertCarrera($data, $ahora);
            }
            return $data;
        });
    }

    cacheParcheHomeDestacadas($db);
    cacheParcheStats($db);
}

/** Elimina carrera del caché (tras DELETE). */
function cacheParcheCarreraEliminada(mysqli $db, int $idCarrera): void
{
    $idCarrera = (int) $idCarrera;
    cacheForget('carrera_' . $idCarrera);
    foreach (cacheKeysByPrefix('catalogo_') as $key) {
        cachePatchIfPresent($key, static function ($data) use ($idCarrera) {
            return is_array($data) ? cacheListaQuitarCarrera($data, $idCarrera) : $data;
        });
    }
    cacheParcheHomeDestacadas($db);
    cacheParcheStats($db);
}

/**
 * Tras create/edit/delete de área: refresca listado y parchea nombre en catálogos/fichas.
 */
function cacheParcheAreas(mysqli $db, ?int $idAreaRenombrada = null, ?string $nombreNuevo = null, bool $eliminada = false): void
{
    $rows = $db->query('SELECT id_area, nombre FROM area ORDER BY nombre')->fetch_all(MYSQLI_ASSOC);
    cachePatch('areas', $rows, CACHE_TTL_AREAS);

    if ($idAreaRenombrada && $nombreNuevo !== null && !$eliminada) {
        $id = (int) $idAreaRenombrada;
        $nom = (string) $nombreNuevo;
        foreach (cacheKeysByPrefix('catalogo_') as $key) {
            cachePatchIfPresent($key, static function ($data) use ($id, $nom) {
                if (!is_array($data)) {
                    return $data;
                }
                foreach ($data as $i => $row) {
                    if (is_array($row) && (int) ($row['id_area'] ?? 0) === $id) {
                        $data[$i]['area'] = $nom;
                    }
                }
                return $data;
            });
        }
        foreach (cacheKeysByPrefix('carrera_') as $key) {
            cachePatchIfPresent($key, static function ($data) use ($id, $nom) {
                if (is_array($data) && (int) ($data['id_area'] ?? 0) === $id) {
                    $data['area'] = $nom;
                }
                return $data;
            });
        }
        cacheParcheHomeDestacadas($db);
    }

    if ($eliminada && $idAreaRenombrada) {
        cacheForgetPrefix('catalogo_a' . (int) $idAreaRenombrada . '_');
    }
}

/**
 * Tras create/edit/delete de universidad: ciudades, stats y datos embebidos en carreras/catálogos.
 */
function cacheParcheUniversidad(mysqli $db, int $idUniversidad, bool $eliminada = false): void
{
    $idUniversidad = (int) $idUniversidad;
    cacheParcheCiudades($db);
    cacheParcheStats($db);

    if ($eliminada || $idUniversidad <= 0) {
        cacheParcheHomeDestacadas($db);
        return;
    }

    $stmt = $db->prepare(
        'SELECT nombre, ciudad, tipo, sitio_web, descripcion FROM universidad WHERE id_universidad = ?'
    );
    $stmt->bind_param('i', $idUniversidad);
    $stmt->execute();
    $uni = $stmt->get_result()->fetch_assoc();
    if (!$uni) {
        cacheParcheHomeDestacadas($db);
        return;
    }

    $nombre = (string) $uni['nombre'];
    $ciudad = (string) $uni['ciudad'];
    $tipo = (string) $uni['tipo'];
    $sitio = (string) ($uni['sitio_web'] ?? '');
    $descUni = (string) ($uni['descripcion'] ?? '');

    cacheRegistrarFiltroCiudad($ciudad);

    foreach (cacheKeysByPrefix('carrera_') as $key) {
        cachePatchIfPresent($key, static function ($data) use ($idUniversidad, $nombre, $ciudad, $tipo, $sitio, $descUni) {
            if (!is_array($data) || (int) ($data['id_universidad'] ?? 0) !== $idUniversidad) {
                return $data;
            }
            $data['universidad'] = $nombre;
            $data['ciudad'] = $ciudad;
            $data['tipo'] = $tipo;
            $data['sitio_web'] = $sitio;
            $data['desc_universidad'] = $descUni;
            return $data;
        });
    }

    // Catálogos: puede cambiar ciudad/tipo → reubicar filas (quitar y reinsertar si aplica)
    $ciudadesIdx = cacheFiltrosCiudadConocidos();
    $idsAfectados = [];
    $peekAll = [];
    foreach (cacheKeysByPrefix('catalogo_') as $key) {
        $peek = cachePeek($key);
        if ($peek === null || !is_array($peek['data'])) {
            continue;
        }
        foreach ($peek['data'] as $row) {
            if (is_array($row) && (int) ($row['id_universidad'] ?? 0) === $idUniversidad) {
                $idsAfectados[(int) $row['id_carrera']] = true;
            }
        }
        $peekAll[$key] = true;
    }

    foreach (array_keys($idsAfectados) as $idCarrera) {
        $ahora = cargarCarreraCompleta($db, (int) $idCarrera);
        foreach (array_keys($peekAll) as $key) {
            [$idArea, $ciudadFiltro, $tipoFiltro] = cacheParseCatalogoKey($key, $ciudadesIdx);
            if ($ciudadFiltro === false) {
                cachePatchIfPresent($key, static function ($data) use ($idCarrera) {
                    return is_array($data) ? cacheListaQuitarCarrera($data, (int) $idCarrera) : $data;
                });
                continue;
            }
            cachePatchIfPresent($key, static function ($data) use ($ahora, $idCarrera, $idArea, $ciudadFiltro, $tipoFiltro) {
                if (!is_array($data)) {
                    return $data;
                }
                $data = cacheListaQuitarCarrera($data, (int) $idCarrera);
                if ($ahora && carreraEntraEnFiltroCatalogo($ahora, $idArea, (string) ($ciudadFiltro ?? ''), $tipoFiltro)) {
                    $data = cacheListaUpsertCarrera($data, $ahora);
                }
                return $data;
            });
        }
    }

    cacheParcheHomeDestacadas($db);
}

