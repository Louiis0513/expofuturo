<?php
session_start();
require_once __DIR__ . "/../includes/conexion.php";
require_once __DIR__ . "/../includes/funciones.php";
requiereAdmin();

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
        guardarMensaje("Universidad eliminada.");
    }
    header("Location: universidades.php");
    exit;
}

$universidades = $mysqli->query(
    "SELECT u.*, COUNT(c.id_carrera) AS total_carreras
     FROM universidad u
     LEFT JOIN carrera c ON c.id_universidad = u.id_universidad
     GROUP BY u.id_universidad
     ORDER BY u.nombre"
)->fetch_all(MYSQLI_ASSOC);

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

    <?php if (count($universidades) === 0): ?>
        <div class="vacio">
            <div class="vacio-icon" aria-hidden="true">+</div>
            <h3>Sin universidades</h3>
            <p>Agrega la primera institución para poder publicar carreras.</p>
            <div class="vacio-acciones">
                <a class="btn btn-dorado" href="universidad_form.php">Nueva universidad</a>
            </div>
        </div>
    <?php else: ?>
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
    <?php endif; ?>
</main>

<?php require __DIR__ . "/../includes/footer.php"; ?>
