<?php
session_start();
require_once __DIR__ . "/includes/conexion.php";
require_once __DIR__ . "/includes/funciones.php";

$q = trim($_GET["q"] ?? "");
$idArea = (int) ($_GET["area"] ?? 0);
$ciudad = trim($_GET["ciudad"] ?? "");
$tipo = trim($_GET["tipo"] ?? "");

$areas = $mysqli->query("SELECT id_area, nombre FROM area ORDER BY nombre")->fetch_all(MYSQLI_ASSOC);

$ciudadesStmt = $mysqli->query("SELECT DISTINCT ciudad FROM universidad ORDER BY ciudad");
$ciudades = array_column($ciudadesStmt->fetch_all(MYSQLI_ASSOC), "ciudad");

$sql = "SELECT c.id_carrera, c.nombre AS carrera, c.duracion_semestres, c.modalidad, c.descripcion,
               a.nombre AS area, u.nombre AS universidad, u.ciudad, u.tipo
        FROM carrera c
        INNER JOIN area a ON a.id_area = c.id_area
        INNER JOIN universidad u ON u.id_universidad = c.id_universidad
        WHERE 1=1";
$types = "";
$params = [];

if ($q !== "") {
    $sql .= " AND (c.nombre LIKE ? OR c.descripcion LIKE ? OR u.nombre LIKE ?)";
    $like = "%" . $q . "%";
    $types .= "sss";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}
if ($idArea > 0) {
    $sql .= " AND c.id_area = ?";
    $types .= "i";
    $params[] = $idArea;
}
if ($ciudad !== "") {
    $sql .= " AND u.ciudad = ?";
    $types .= "s";
    $params[] = $ciudad;
}
if ($tipo === "publica" || $tipo === "privada") {
    $sql .= " AND u.tipo = ?";
    $types .= "s";
    $params[] = $tipo;
}

$sql .= " ORDER BY c.nombre ASC";

$stmt = $mysqli->prepare($sql);
if ($types !== "") {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$carreras = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$hayFiltros = ($q !== "" || $idArea > 0 || $ciudad !== "" || $tipo !== "");
$nombreAreaActiva = "";
foreach ($areas as $a) {
    if ((int) $a["id_area"] === $idArea) {
        $nombreAreaActiva = $a["nombre"];
        break;
    }
}

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
        <p class="hero-lead">Compara universidades y carreras de Bogotá, Medellín, Cali, Barranquilla y más. Filtra por área, ciudad y tipo de institución.</p>
        <div class="hero-actions">
            <a class="btn btn-dorado" href="#buscar">Explorar carreras</a>
            <a class="hero-scroll" href="#buscar"><?= $totalCarrerasDb ?> programas · <?= $totalUni ?> universidades ↓</a>
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
            <a class="btn btn-dorado" href="#buscar">Empezar a filtrar</a>
        </div>
    </section>

    <section class="panel filtros-panel" id="buscar">
        <h2>Buscar carreras</h2>
        <p class="seccion-sub">Combina texto, área de conocimiento, ciudad y tipo de universidad.</p>
        <form class="filtros" method="get" action="index.php#buscar">
            <div class="campo">
                <label for="q">Texto</label>
                <input type="search" id="q" name="q" placeholder="Ej. sistemas, medicina, EAFIT…" value="<?= e($q) ?>">
            </div>
            <div class="campo">
                <label for="area">Área</label>
                <select id="area" name="area">
                    <option value="">Todas</option>
                    <?php foreach ($areas as $area): ?>
                        <option value="<?= (int) $area["id_area"] ?>" <?= $idArea === (int) $area["id_area"] ? "selected" : "" ?>>
                            <?= e($area["nombre"]) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="campo">
                <label for="ciudad">Ciudad</label>
                <select id="ciudad" name="ciudad">
                    <option value="">Todas</option>
                    <?php foreach ($ciudades as $c): ?>
                        <option value="<?= e($c) ?>" <?= $ciudad === $c ? "selected" : "" ?>><?= e($c) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="campo">
                <label for="tipo">Tipo</label>
                <select id="tipo" name="tipo">
                    <option value="">Todas</option>
                    <option value="publica" <?= $tipo === "publica" ? "selected" : "" ?>>Pública</option>
                    <option value="privada" <?= $tipo === "privada" ? "selected" : "" ?>>Privada</option>
                </select>
            </div>
            <div class="filtros-acciones">
                <button class="btn btn-dorado" type="submit">Buscar</button>
                <?php if ($hayFiltros): ?>
                    <a class="btn btn-ghost" href="index.php#buscar">Limpiar</a>
                <?php endif; ?>
            </div>
        </form>

        <?php if ($hayFiltros): ?>
            <div class="chips" aria-label="Filtros activos">
                <?php if ($q !== ""): ?>
                    <span class="chip">Texto: “<?= e($q) ?>”</span>
                <?php endif; ?>
                <?php if ($nombreAreaActiva !== ""): ?>
                    <span class="chip">Área: <?= e($nombreAreaActiva) ?></span>
                <?php endif; ?>
                <?php if ($ciudad !== ""): ?>
                    <span class="chip">Ciudad: <?= e($ciudad) ?></span>
                <?php endif; ?>
                <?php if ($tipo === "publica" || $tipo === "privada"): ?>
                    <span class="chip">Tipo: <?= e(etiquetaTipo($tipo)) ?></span>
                <?php endif; ?>
                <a class="chip chip-quitar" href="index.php#buscar" title="Quitar todos los filtros">× Limpiar</a>
            </div>
        <?php endif; ?>
    </section>

    <?php if (count($carreras) === 0): ?>
        <div class="vacio">
            <div class="vacio-icon" aria-hidden="true">?</div>
            <h3>Sin resultados con esos filtros</h3>
            <p>Prueba ampliar la búsqueda: quita la ciudad, cambia el área o busca solo por una palabra (por ejemplo “ingeniería” o “Medellín”).</p>
            <div class="vacio-acciones">
                <a class="btn btn-dorado" href="index.php#buscar">Ver todas las carreras</a>
                <a class="btn btn-outline" href="index.php?ciudad=Bogotá#buscar">Carreras en Bogotá</a>
                <a class="btn btn-outline" href="index.php?tipo=publica#buscar">Solo públicas</a>
            </div>
        </div>
    <?php else: ?>
        <div class="resultado-bar">
            <strong><?= count($carreras) ?> carrera<?= count($carreras) === 1 ? "" : "s" ?></strong>
            <span><?= $hayFiltros ? "Resultados filtrados" : "Catálogo completo" ?></span>
        </div>
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
    <?php endif; ?>
</main>

<?php require __DIR__ . "/includes/footer.php"; ?>
