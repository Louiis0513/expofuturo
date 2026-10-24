<?php
session_start();
require_once __DIR__ . "/includes/conexion.php";
require_once __DIR__ . "/includes/funciones.php";

$carreras = $mysqli->query(
    "SELECT c.id_carrera, c.nombre AS carrera, c.duracion_semestres, c.modalidad, c.descripcion,
            a.nombre AS area, u.nombre AS universidad, u.ciudad, u.tipo
     FROM carrera c
     INNER JOIN area a ON a.id_area = c.id_area
     INNER JOIN universidad u ON u.id_universidad = c.id_universidad
     ORDER BY c.nombre ASC
     LIMIT 6"
)->fetch_all(MYSQLI_ASSOC);

$totalUni = (int) $mysqli->query("SELECT COUNT(*) AS c FROM universidad")->fetch_assoc()["c"];
$totalCarrerasDb = (int) $mysqli->query("SELECT COUNT(*) AS c FROM carrera")->fetch_assoc()["c"];

require __DIR__ . "/includes/header.php";
?>

<section class="hero">
    <div class="hero-bg">
        <img src="img/hero-campus.jpg" alt="Campus universitario" width="1600" height="1067">
    </div>
    <div class="hero-inner">
        <p class="hero-eyebrow">Orientación universitaria · Colombia</p>
        <h1>Expo<em>Futuro</em></h1>
        <p class="hero-lead">Compara universidades y carreras de Bogotá, Medellín, Cali, Barranquilla y más. Explora el catálogo por área, ciudad y tipo de institución.</p>
        <div class="hero-actions">
            <a class="btn btn-dorado" href="buscar.php">Explorar carreras</a>
            <a class="hero-scroll" href="#destacadas"><?= $totalCarrerasDb ?> programas · <?= $totalUni ?> universidades ↓</a>
        </div>
    </div>
</section>

<main class="contenedor">
    <section class="spotlight" aria-label="Por qué ExpoFuturo">
        <div class="spotlight-img">
            <img src="img/estudiantes.jpg" alt="Estudiantes en campus" width="1200" height="800">
        </div>
        <div class="spotlight-txt">
            <h2>Decide con información clara</h2>
            <p>Datos reales de instituciones colombianas: duración, modalidad, ciudad y tipo (pública o privada). Ideal para bachilleres y familias en orientación vocacional.</p>
            <a class="btn btn-dorado" href="buscar.php">Buscar en el catálogo</a>
        </div>
    </section>

    <section id="destacadas" aria-labelledby="destacadas-titulo">
        <div class="destacadas-head">
            <div>
                <h2 id="destacadas-titulo">Carreras destacadas</h2>
                <p class="seccion-sub">Seis programas del catálogo para empezar a explorar.</p>
            </div>
            <a class="btn btn-outline" href="buscar.php">Ver todas · filtrar</a>
        </div>

        <?php if (count($carreras) === 0): ?>
            <div class="vacio">
                <div class="vacio-icon" aria-hidden="true">?</div>
                <h3>Aún no hay carreras publicadas</h3>
                <p>Cuando el catálogo tenga programas, aparecerán aquí.</p>
            </div>
        <?php else: ?>
            <div class="rejilla">
                <?php foreach ($carreras as $carrera): ?>
                    <article class="tarjeta">
                        <div class="tarjeta-media">
                            <img src="<?= e(imagenArea($carrera["area"])) ?>" alt="" width="800" height="534" loading="lazy">
                        </div>
                        <div class="tarjeta-body">
                            <div class="meta">
                                <span class="badge badge-navy"><?= e($carrera["area"]) ?></span>
                                <span class="badge badge-gold"><?= e($carrera["ciudad"]) ?></span>
                                <span class="badge"><?= e(etiquetaTipo($carrera["tipo"])) ?></span>
                            </div>
                            <h3><?= e($carrera["carrera"]) ?></h3>
                            <p class="tarjeta-uni"><?= e($carrera["universidad"]) ?></p>
                            <p class="tarjeta-desc"><?= e(mb_strimwidth($carrera["descripcion"] ?? "", 0, 110, "…")) ?></p>
                            <div class="tarjeta-foot">
                                <span class="tarjeta-meta"><?= (int) $carrera["duracion_semestres"] ?> sem. · <?= e($carrera["modalidad"]) ?></span>
                                <a class="btn btn-navy btn-sm" href="carrera.php?id=<?= (int) $carrera["id_carrera"] ?>">Ver detalle</a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
            <p style="text-align:center;margin-top:1.5rem;">
                <a class="btn btn-dorado" href="buscar.php">Explorar catálogo completo</a>
            </p>
        <?php endif; ?>
    </section>
</main>

<?php require __DIR__ . "/includes/footer.php"; ?>
