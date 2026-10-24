<?php
session_start();
require_once __DIR__ . "/../includes/conexion.php";
require_once __DIR__ . "/../includes/funciones.php";
requiereAdmin();

$totalUniversidades = (int) $mysqli->query("SELECT COUNT(*) AS c FROM universidad")->fetch_assoc()["c"];
$totalCarreras = (int) $mysqli->query("SELECT COUNT(*) AS c FROM carrera")->fetch_assoc()["c"];
$totalAreas = (int) $mysqli->query("SELECT COUNT(*) AS c FROM area")->fetch_assoc()["c"];

require __DIR__ . "/../includes/header_admin.php";
?>

<main class="contenedor">
    <div class="page-heading">
        <div>
            <h1>Panel principal</h1>
            <p>Hola, <?= e($_SESSION["admin"]["nombre"] ?? "administrador") ?>. Resumen del catálogo.</p>
        </div>
    </div>

    <div class="stats">
        <div class="stat">
            <div class="numero"><?= $totalUniversidades ?></div>
            <div class="etiqueta">Universidades</div>
        </div>
        <div class="stat">
            <div class="numero"><?= $totalCarreras ?></div>
            <div class="etiqueta">Carreras</div>
        </div>
        <div class="stat">
            <div class="numero"><?= $totalAreas ?></div>
            <div class="etiqueta">Áreas</div>
        </div>
    </div>

    <section class="panel" style="margin-top:1.5rem;">
        <h2>Accesos rápidos</h2>
        <div class="admin-quick">
            <a href="areas.php">Áreas<span>Clasificación del catálogo</span></a>
            <a href="universidades.php">Universidades<span>Instituciones y ciudades</span></a>
            <a href="carreras.php">Carreras<span>Oferta académica publicada</span></a>
            <a href="carrera_form.php">＋ Nueva carrera<span>Agregar programa</span></a>
        </div>
    </section>
</main>

<?php require __DIR__ . "/../includes/footer.php"; ?>
