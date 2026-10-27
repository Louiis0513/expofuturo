<?php
session_start();
require_once __DIR__ . "/../includes/conexion.php";
require_once __DIR__ . "/../includes/funciones.php";
enviarHeadersCache('privado');
requiereAdmin();

$q = trim($_GET["q"] ?? "");
$idArea = (int) ($_GET["area"] ?? 0);
$ciudad = trim($_GET["ciudad"] ?? "");
$modalidad = trim($_GET["modalidad"] ?? "");

if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["accion"] ?? "") === "eliminar") {
    $id = (int) ($_POST["id_carrera"] ?? 0);
    $del = $mysqli->prepare("DELETE FROM carrera WHERE id_carrera = ?");
    $del->bind_param("i", $id);
    $del->execute();
    cacheParcheCarreraEliminada($mysqli, $id);
    guardarMensaje("Carrera eliminada.");
    header("Location: carreras.php");
    exit;
}

$areas = obtenerAreas($mysqli);
$ciudades = obtenerCiudades($mysqli);

$where = " WHERE 1=1";
$types = "";
$params = [];

if ($q !== "") {
    $where .= " AND (c.nombre LIKE ? OR u.nombre LIKE ?)";
    $like = "%" . $q . "%";
    $types .= "ss";
    $params[] = $like;
    $params[] = $like;
}
if ($idArea > 0) {
    $where .= " AND c.id_area = ?";
    $types .= "i";
    $params[] = $idArea;
}
if ($ciudad !== "") {
    $where .= " AND u.ciudad = ?";
    $types .= "s";
    $params[] = $ciudad;
}
if ($modalidad !== "") {
    $where .= " AND c.modalidad = ?";
    $types .= "s";
    $params[] = $modalidad;
}

$countSql = "SELECT COUNT(*) AS total
             FROM carrera c
             INNER JOIN area a ON a.id_area = c.id_area
             INNER JOIN universidad u ON u.id_universidad = c.id_universidad"
          . $where;
$countStmt = $mysqli->prepare($countSql);
if ($types !== "") {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$total = (int) $countStmt->get_result()->fetch_assoc()["total"];

$pag = calcularPaginacion($total, 10);
$queryParams = [
    "q" => $q,
    "area" => $idArea > 0 ? $idArea : "",
    "ciudad" => $ciudad,
    "modalidad" => $modalidad,
];
$hayFiltros = ($q !== "" || $idArea > 0 || $ciudad !== "" || $modalidad !== "");

$sql = "SELECT c.id_carrera, c.nombre, c.duracion_semestres, c.modalidad,
               a.nombre AS area, u.nombre AS universidad, u.ciudad
        FROM carrera c
        INNER JOIN area a ON a.id_area = c.id_area
        INNER JOIN universidad u ON u.id_universidad = c.id_universidad"
     . $where
     . " ORDER BY c.nombre
        LIMIT ? OFFSET ?";

$listTypes = $types . "ii";
$listParams = $params;
$listParams[] = $pag["por_pagina"];
$listParams[] = $pag["offset"];

$stmt = $mysqli->prepare($sql);
$stmt->bind_param($listTypes, ...$listParams);
$stmt->execute();
$carreras = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

require __DIR__ . "/../includes/header_admin.php";
?>

<main class="contenedor">
    <div class="page-heading">
        <div>
            <h1>Carreras</h1>
            <p>Gestiona la oferta académica publicada en ExpoFuturo.</p>
        </div>
        <a class="btn btn-dorado" href="carrera_form.php">＋ Nueva carrera</a>
    </div>

    <section class="panel filtros-panel">
        <form class="filtros filtros-admin" method="get" action="carreras.php">
            <div class="campo">
                <label for="q">Texto</label>
                <input type="search" id="q" name="q" placeholder="Carrera o universidad…" value="<?= e($q) ?>">
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
                <label for="modalidad">Modalidad</label>
                <select id="modalidad" name="modalidad">
                    <option value="">Todas</option>
                    <option value="Presencial" <?= $modalidad === "Presencial" ? "selected" : "" ?>>Presencial</option>
                    <option value="Virtual" <?= $modalidad === "Virtual" ? "selected" : "" ?>>Virtual</option>
                    <option value="Híbrida" <?= $modalidad === "Híbrida" ? "selected" : "" ?>>Híbrida</option>
                </select>
            </div>
            <div class="filtros-acciones">
                <button class="btn btn-dorado" type="submit">Filtrar</button>
                <?php if ($hayFiltros): ?>
                    <a class="btn btn-ghost" href="carreras.php">Limpiar</a>
                <?php endif; ?>
            </div>
        </form>
    </section>

    <?php if ($total === 0): ?>
        <div class="vacio">
            <div class="vacio-icon" aria-hidden="true">+</div>
            <h3><?= $hayFiltros ? "Sin resultados" : "Aún no hay carreras" ?></h3>
            <p><?= $hayFiltros ? "Ninguna carrera coincide con los filtros." : "Crea la primera carrera y asígnala a un área y una universidad." ?></p>
            <?php if (!$hayFiltros): ?>
                <div class="vacio-acciones">
                    <a class="btn btn-dorado" href="carrera_form.php">Nueva carrera</a>
                </div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="resultado-bar">
            <strong><?= $total ?> carrera<?= $total === 1 ? "" : "s" ?></strong>
            <span>Mostrando <?= $pag["desde"] ?>–<?= $pag["hasta"] ?></span>
        </div>

        <section class="panel table-desktop">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Carrera</th>
                            <th>Área</th>
                            <th>Universidad</th>
                            <th>Ciudad</th>
                            <th>Duración</th>
                            <th>Modalidad</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($carreras as $c): ?>
                            <tr>
                                <td><?= e($c["nombre"]) ?></td>
                                <td><?= e($c["area"]) ?></td>
                                <td><?= e($c["universidad"]) ?></td>
                                <td><?= e($c["ciudad"]) ?></td>
                                <td><?= (int) $c["duracion_semestres"] ?> sem.</td>
                                <td><?= e($c["modalidad"]) ?></td>
                                <td>
                                    <div class="acciones">
                                        <a class="btn btn-navy btn-sm" href="carrera_form.php?id=<?= (int) $c["id_carrera"] ?>">Editar</a>
                                        <form method="post" onsubmit="return confirm('¿Eliminar esta carrera?');">
                                            <input type="hidden" name="accion" value="eliminar">
                                            <input type="hidden" name="id_carrera" value="<?= (int) $c["id_carrera"] ?>">
                                            <button class="btn btn-peligro btn-sm" type="submit">Eliminar</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <div class="admin-cards">
            <?php foreach ($carreras as $c): ?>
                <article class="admin-card">
                    <h3><?= e($c["nombre"]) ?></h3>
                    <dl>
                        <dt>Área</dt><dd><?= e($c["area"]) ?></dd>
                        <dt>Universidad</dt><dd><?= e($c["universidad"]) ?></dd>
                        <dt>Ciudad</dt><dd><?= e($c["ciudad"]) ?></dd>
                        <dt>Duración / modalidad</dt>
                        <dd><?= (int) $c["duracion_semestres"] ?> sem. · <?= e($c["modalidad"]) ?></dd>
                    </dl>
                    <div class="acciones">
                        <a class="btn btn-navy btn-sm" href="carrera_form.php?id=<?= (int) $c["id_carrera"] ?>">Editar</a>
                        <form method="post" onsubmit="return confirm('¿Eliminar esta carrera?');">
                            <input type="hidden" name="accion" value="eliminar">
                            <input type="hidden" name="id_carrera" value="<?= (int) $c["id_carrera"] ?>">
                            <button class="btn btn-peligro btn-sm" type="submit">Eliminar</button>
                        </form>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <?= renderPaginacion($pag, "carreras.php", $queryParams) ?>
    <?php endif; ?>
</main>

<?php require __DIR__ . "/../includes/footer.php"; ?>
