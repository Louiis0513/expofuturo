<?php
session_start();
require_once __DIR__ . "/includes/conexion.php";
require_once __DIR__ . "/includes/funciones.php";
enviarHeadersCache('publico');

$id = (int) ($_GET["id"] ?? 0);
if ($id <= 0) {
    guardarMensaje("Carrera no encontrada.");
    header("Location: index.php");
    exit;
}

$carrera = obtenerCarrera($mysqli, $id);

if (!$carrera) {
    guardarMensaje("Carrera no encontrada.");
    header("Location: index.php");
    exit;
}

$idArea = (int) $carrera["id_area"];
$relacionadas = obtenerCarrerasRelacionadas($mysqli, $idArea, $id, 3);

require __DIR__ . "/includes/header.php";
?>

<main class="contenedor detalle">
    <section class="detalle-hero">
        <img src="<?= e(imagenArea($carrera["area"])) ?>" alt="" width="800" height="534">
        <div class="detalle-hero-inner">
            <p class="breadcrumb">
                <a href="buscar.php">Carreras</a> ·
                <a href="buscar.php?area=<?= $idArea ?>"><?= e($carrera["area"]) ?></a>
            </p>
            <h1><?= e($carrera["carrera"]) ?></h1>
            <p class="uni-line"><?= e($carrera["universidad"]) ?> · <?= e($carrera["ciudad"]) ?></p>
            <div class="meta">
                <span class="badge"><?= e($carrera["area"]) ?></span>
                <span class="badge"><?= (int) $carrera["duracion_semestres"] ?> semestres</span>
                <span class="badge"><?= e($carrera["modalidad"]) ?></span>
                <span class="badge"><?= e(etiquetaTipo($carrera["tipo"])) ?></span>
            </div>
        </div>
    </section>

    <div class="detalle-grid">
        <section class="panel bloque-info">
            <h2>Sobre la carrera</h2>
            <p><?= e($carrera["desc_carrera"] ?: "Sin descripción disponible.") ?></p>
            <ul class="lista-datos">
                <li><strong>Área</strong> <span><?= e($carrera["area"]) ?></span></li>
                <li><strong>Duración</strong> <span><?= (int) $carrera["duracion_semestres"] ?> semestres</span></li>
                <li><strong>Modalidad</strong> <span><?= e($carrera["modalidad"]) ?></span></li>
                <li><strong>Ciudad</strong> <span><?= e($carrera["ciudad"]) ?></span></li>
            </ul>
            <div class="acciones-detalle">
                <a class="btn btn-outline" href="buscar.php">← Volver a buscar</a>
                <a class="btn btn-ghost" href="buscar.php?area=<?= $idArea ?>">Más de <?= e($carrera["area"]) ?></a>
            </div>
        </section>

        <section class="panel bloque-info">
            <h2>Universidad</h2>
            <p><?= e($carrera["desc_universidad"] ?: "Sin descripción disponible.") ?></p>
            <ul class="lista-datos">
                <li><strong>Nombre</strong> <span><?= e($carrera["universidad"]) ?></span></li>
                <li><strong>Ciudad</strong> <span><?= e($carrera["ciudad"]) ?></span></li>
                <li><strong>Tipo</strong> <span><?= e(etiquetaTipo($carrera["tipo"])) ?></span></li>
            </ul>
            <?php if (!empty($carrera["sitio_web"])): ?>
                <div class="acciones-detalle">
                    <a class="btn btn-dorado" href="<?= e($carrera["sitio_web"]) ?>" target="_blank" rel="noopener noreferrer">Visitar sitio web</a>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <?php if (count($relacionadas) > 0): ?>
        <section class="relacionadas">
            <h2>Otras carreras del mismo área</h2>
            <div class="rejilla">
                <?php foreach ($relacionadas as $r): ?>
                    <article class="tarjeta">
                        <div class="tarjeta-media">
                            <img src="<?= e(imagenArea($r["area"])) ?>" alt="" loading="lazy" width="800" height="534">
                        </div>
                        <div class="tarjeta-body">
                            <div class="meta">
                                <span class="badge badge-gold"><?= e($r["ciudad"]) ?></span>
                            </div>
                            <h3><?= e($r["carrera"]) ?></h3>
                            <p class="tarjeta-uni"><?= e($r["universidad"]) ?></p>
                            <div class="tarjeta-foot">
                                <span class="tarjeta-meta"><?= e($r["area"]) ?></span>
                                <a class="btn btn-navy btn-sm" href="carrera.php?id=<?= (int) $r["id_carrera"] ?>">Ver</a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</main>

<?php require __DIR__ . "/includes/footer.php"; ?>
