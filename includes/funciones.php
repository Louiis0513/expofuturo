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
