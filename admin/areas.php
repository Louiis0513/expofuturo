<?php
session_start();
require_once __DIR__ . "/../includes/conexion.php";
require_once __DIR__ . "/../includes/funciones.php";
requiereAdmin();

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $accion = $_POST["accion"] ?? "";

    if ($accion === "crear") {
        $nombre = trim($_POST["nombre"] ?? "");
        if ($nombre === "" || mb_strlen($nombre) > 100) {
            $error = "El nombre del área es obligatorio (máx. 100 caracteres).";
        } else {
            try {
                $stmt = $mysqli->prepare("INSERT INTO area (nombre) VALUES (?)");
                $stmt->bind_param("s", $nombre);
                $stmt->execute();
                guardarMensaje("Área creada.");
                header("Location: areas.php");
                exit;
            } catch (mysqli_sql_exception $e) {
                $error = ($mysqli->errno === 1062) ? "Esa área ya existe." : "No se pudo crear el área.";
            }
        }
    }

    if ($accion === "actualizar") {
        $id = (int) ($_POST["id_area"] ?? 0);
        $nombre = trim($_POST["nombre"] ?? "");
        if ($id <= 0 || $nombre === "" || mb_strlen($nombre) > 100) {
            $error = "Datos inválidos para actualizar.";
        } else {
            try {
                $stmt = $mysqli->prepare("UPDATE area SET nombre = ? WHERE id_area = ?");
                $stmt->bind_param("si", $nombre, $id);
                $stmt->execute();
                guardarMensaje("Área actualizada.");
                header("Location: areas.php");
                exit;
            } catch (mysqli_sql_exception $e) {
                $error = ($mysqli->errno === 1062) ? "Esa área ya existe." : "No se pudo actualizar el área.";
            }
        }
    }

    if ($accion === "eliminar") {
        $id = (int) ($_POST["id_area"] ?? 0);
        $check = $mysqli->prepare("SELECT COUNT(*) AS c FROM carrera WHERE id_area = ?");
        $check->bind_param("i", $id);
        $check->execute();
        $tiene = (int) $check->get_result()->fetch_assoc()["c"];

        if ($tiene > 0) {
            $error = "No se puede eliminar: hay carreras asociadas a esta área.";
        } else {
            $del = $mysqli->prepare("DELETE FROM area WHERE id_area = ?");
            $del->bind_param("i", $id);
            $del->execute();
            guardarMensaje("Área eliminada.");
            header("Location: areas.php");
            exit;
        }
    }
}

$areas = $mysqli->query("SELECT a.id_area, a.nombre, COUNT(c.id_carrera) AS total_carreras
                         FROM area a
                         LEFT JOIN carrera c ON c.id_area = a.id_area
                         GROUP BY a.id_area, a.nombre
                         ORDER BY a.nombre")->fetch_all(MYSQLI_ASSOC);

require __DIR__ . "/../includes/header_admin.php";
?>

<main class="contenedor">
    <div class="page-heading">
        <div>
            <h1>Áreas</h1>
            <p>Administra las áreas de conocimiento del catálogo.</p>
        </div>
    </div>

    <section class="panel">
        <h2>Nueva área</h2>
        <?php if ($error !== ""): ?>
            <p class="form-error"><?= e($error) ?></p>
        <?php endif; ?>
        <form class="form-inline" method="post">
            <input type="hidden" name="accion" value="crear">
            <input type="text" name="nombre" placeholder="Nombre del área" required maxlength="100">
            <button class="btn btn-dorado" type="submit">Agregar</button>
        </form>
    </section>

    <?php if (count($areas) === 0): ?>
        <div class="vacio">
            <div class="vacio-icon" aria-hidden="true">+</div>
            <h3>Sin áreas</h3>
            <p>Crea la primera área de conocimiento para clasificar las carreras.</p>
        </div>
    <?php else: ?>
        <section class="panel table-desktop">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Carreras</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($areas as $area): ?>
                            <tr>
                                <td>
                                    <form method="post" class="form-inline">
                                        <input type="hidden" name="accion" value="actualizar">
                                        <input type="hidden" name="id_area" value="<?= (int) $area["id_area"] ?>">
                                        <input type="text" name="nombre" value="<?= e($area["nombre"]) ?>" required maxlength="100">
                                        <button class="btn btn-navy btn-sm" type="submit">Guardar</button>
                                    </form>
                                </td>
                                <td><?= (int) $area["total_carreras"] ?></td>
                                <td>
                                    <form method="post" onsubmit="return confirm('¿Eliminar esta área?');">
                                        <input type="hidden" name="accion" value="eliminar">
                                        <input type="hidden" name="id_area" value="<?= (int) $area["id_area"] ?>">
                                        <button class="btn btn-peligro btn-sm" type="submit">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <div class="admin-cards">
            <?php foreach ($areas as $area): ?>
                <article class="admin-card">
                    <form method="post" class="form-inline" style="margin-bottom:0.75rem;">
                        <input type="hidden" name="accion" value="actualizar">
                        <input type="hidden" name="id_area" value="<?= (int) $area["id_area"] ?>">
                        <input type="text" name="nombre" value="<?= e($area["nombre"]) ?>" required maxlength="100">
                        <button class="btn btn-navy btn-sm" type="submit">Guardar</button>
                    </form>
                    <dl>
                        <dt>Carreras asociadas</dt>
                        <dd><?= (int) $area["total_carreras"] ?></dd>
                    </dl>
                    <form method="post" onsubmit="return confirm('¿Eliminar esta área?');">
                        <input type="hidden" name="accion" value="eliminar">
                        <input type="hidden" name="id_area" value="<?= (int) $area["id_area"] ?>">
                        <button class="btn btn-peligro btn-sm" type="submit">Eliminar</button>
                    </form>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<?php require __DIR__ . "/../includes/footer.php"; ?>
