<?php
$mensaje = $mensaje ?? "";
if ($mensaje === "") {
    $mensaje = obtenerMensaje();
}
$paginaActual = basename($_SERVER["PHP_SELF"] ?? "index.php");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel ExpoFuturo</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;600;700&family=Libre+Baskerville:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/estilos.css?v=4">
</head>
<body class="admin-body">

<header>
    <nav class="nav nav-admin">
        <a href="index.php" class="nav-marca">
            <img src="../img/logo.png" alt="Logo ExpoFuturo">
            <span>Panel ExpoFuturo</span>
        </a>
        <button type="button" class="nav-toggle" id="navToggle" aria-expanded="false" aria-controls="navLinks" aria-label="Abrir menú">
            <span class="nav-toggle-bars" aria-hidden="true"><i></i><i></i><i></i></span>
            <span class="nav-toggle-text">Menú</span>
        </button>
        <div class="nav-links" id="navLinks">
            <a href="index.php" class="<?= $paginaActual === "index.php" ? "activo" : "" ?>">Inicio</a>
            <a href="areas.php" class="<?= $paginaActual === "areas.php" ? "activo" : "" ?>">Áreas</a>
            <a href="universidades.php" class="<?= str_starts_with($paginaActual, "universidad") ? "activo" : "" ?>">Universidades</a>
            <a href="carreras.php" class="<?= str_starts_with($paginaActual, "carrera") ? "activo" : "" ?>">Carreras</a>
            <a href="../index.php">Ver sitio</a>
            <a href="logout.php">Cerrar sesión</a>
        </div>
    </nav>
</header>

<?php if ($mensaje !== ""): ?>
    <p class="mensaje"><?= e($mensaje) ?></p>
<?php endif; ?>
