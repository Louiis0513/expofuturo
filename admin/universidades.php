<?php
session_start();
require_once __DIR__ . "/../includes/conexion.php";
require_once __DIR__ . "/../includes/funciones.php";
enviarHeadersCache('privado');
requiereAdmin();

$q = trim($_GET["q"] ?? "");
$ciudad = trim($_GET["ciudad"] ?? "");
$tipo = trim($_GET["tipo"] ?? "");

if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["accion"] ?? "") === "eliminar") {
    $id = (int) ($_POST["id_universidad"] ?? 0);
    $check = $mysqli->prepare("SELECT COUNT(*) AS c FROM carrera WHERE id_universidad = ?");
    $check->bind_param("i", $id);
    $check->execute();
    $tiene = (int) $check->get_result()->fetch_assoc()["c"];

    if ($tiene > 0) {
        guardarMensaje("No se puede eliminar: hay carreras asociadas a esta universidad.");
    } else {
        $del = $mysqli->prepare("DELETE FROM universidad WHERE id_universidad = ?");
        $del->bind_param("i", $id);
        $del->execute();
        cacheParcheUniversidad($mysqli, $id, true);
        guardarMensaje("Universidad eliminada.");
    }
    header("Location: universidades.php");
    exit;
}

$ciudades = obtenerCiudades($mysqli);

$where = " WHERE 1=1";
$types = "";
$params = [];

if ($q !== "") {
    $where .= " AND (u.nombre LIKE ? OR u.descripcion LIKE ?)";
    $like = "%" . $q . "%";
    $types .= "ss";
    $params[] = $like;
    $params[] = $like;
}
if ($ciudad !== "") {
    $where .= " AND u.ciudad = ?";
    $types .= "s";
    $params[] = $ciudad;
}
if ($tipo === "publica" || $tipo === "privada") {
    $where .= " AND u.tipo = ?";
    $types .= "s";
    $params[] = $tipo;
}

$countSql = "SELECT COUNT(*) AS total FROM universidad u" . $where;
$countStmt = $mysqli->prepare($countSql);
if ($types !== "") {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$total = (int) $countStmt->get_result()->fetch_assoc()["total"];

$pag = calcularPaginacion($total, 10);
$queryParams = [
    "q" => $q,
    "ciudad" => $ciudad,
    "tipo" => $tipo,
];
$hayFiltros = ($q !== "" || $ciudad !== "" || $tipo !== "");

$sql = "SELECT u.*, COUNT(c.id_carrera) AS total_carreras
        FROM universidad u
        LEFT JOIN carrera c ON c.id_universidad = u.id_universidad"
     . $where
     . " GROUP BY u.id_universidad
        ORDER BY u.nombre
        LIMIT ? OFFSET ?";

$listTypes = $types . "ii";
$listParams = $params;
$listParams[] = $pag["por_pagina"];
$listParams[] = $pag["offset"];

$stmt = $mysqli->prepare($sql);
$stmt->bind_param($listTypes, ...$listParams);
$stmt->execute();
$universidades = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

require __DIR__ . "/../includes/header_admin.php";
?>

<main class="contenedor">
    <div class="page-heading">
        <div>
            <h1>Universidades</h1>
            <p>Catálogo de instituciones educativas.</p>
        </div>
        <a class="btn btn-dorado" href="universidad_form.php">＋ Nueva universidad</a>
    </div>

    <section class="panel filtros-panel">
        <form class="filtros filtros-admin" method="get" action="universidades.php">
            <div class="campo">
                <label for="q">Texto</label>
                <input type="search" id="q" name="q" placeholder="Nombre…" value="<?= e($q) ?>">
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
                <button class="btn btn-dorado" type="submit">Filtrar</button>
                <?php if ($hayFiltros): ?>
                    <a class="btn btn-ghost" href="universidades.php">Limpiar</a>
                <?php endif; ?>
            </div>
        </form>
    </section>

    <?php if ($total === 0): ?>
        <div class="vacio">
            <div class="vacio-icon" aria-hidden="true">+</div>
            <h3><?= $hayFiltros ? "Sin resultados" : "Sin universidades" ?></h3>
            <p><?= $hayFiltros ? "Ninguna universidad coincide con los filtros." : "Agrega la primera institución para poder publicar carreras." ?></p>
            <?php if (!$hayFiltros): ?>
                <div class="vacio-acciones">
                    <a class="btn btn-dorado" href="universidad_form.php">Nueva universidad</a>
                </div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="resultado-bar">
            <strong><?= $total ?> universidad<?= $total === 1 ? "" : "es" ?></strong>
            <span>Mostrando <?= $pag["desde"] ?>–<?= $pag["hasta"] ?></span>
        </div>

        <section class="panel table-desktop">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Ciudad</th>
                            <th>Tipo</th>
                            <th>Carreras</th>
                            <th>Sitio web</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($universidades as $u): ?>
                            <tr>
                                <td><?= e($u["nombre"]) ?></td>
                                <td><?= e($u["ciudad"]) ?></td>
                                <td><?= e(etiquetaTipo($u["tipo"])) ?></td>
                                <td><?= (int) $u["total_carreras"] ?></td>
                                <td>
                                    <?php if (!empty($u["sitio_web"])): ?>
                                        <a href="<?= e($u["sitio_web"]) ?>" target="_blank" rel="noopener noreferrer">Visitar</a>
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="acciones">
                                        <a class="btn btn-navy btn-sm" href="universidad_form.php?id=<?= (int) $u["id_universidad"] ?>">Editar</a>
                                        <form method="post" onsubmit="return confirm('¿Eliminar esta universidad?');">
                                            <input type="hidden" name="accion" value="eliminar">
                                            <input type="hidden" name="id_universidad" value="<?= (int) $u["id_universidad"] ?>">
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
            <?php foreach ($universidades as $u): ?>
                <article class="admin-card">
                    <h3><?= e($u["nombre"]) ?></h3>
                    <dl>
                        <dt>Ciudad</dt><dd><?= e($u["ciudad"]) ?></dd>
                        <dt>Tipo</dt><dd><?= e(etiquetaTipo($u["tipo"])) ?></dd>
                        <dt>Carreras</dt><dd><?= (int) $u["total_carreras"] ?></dd>
                    </dl>
                    <div class="acciones">
                        <a class="btn btn-navy btn-sm" href="universidad_form.php?id=<?= (int) $u["id_universidad"] ?>">Editar</a>
                        <form method="post" onsubmit="return confirm('¿Eliminar esta universidad?');">
                            <input type="hidden" name="accion" value="eliminar">
                            <input type="hidden" name="id_universidad" value="<?= (int) $u["id_universidad"] ?>">
                            <button class="btn btn-peligro btn-sm" type="submit">Eliminar</button>
                        </form>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <?= renderPaginacion($pag, "universidades.php", $queryParams) ?>
    <?php endif; ?>
</main>

<?php require __DIR__ . "/../includes/footer.php"; ?>
