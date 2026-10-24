<?php
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
