<?php
session_start();
require_once __DIR__ . "/../includes/conexion.php";
require_once __DIR__ . "/../includes/funciones.php";
requiereAdmin();

if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["accion"] ?? "") === "eliminar") {
    $id = (int) ($_POST["id_carrera"] ?? 0);
    $del = $mysqli->prepare("DELETE FROM carrera WHERE id_carrera = ?");
    $del->bind_param("i", $id);
    $del->execute();
    guardarMensaje("Carrera eliminada.");
    header("Location: carreras.php");
    exit;
}

$carreras = $mysqli->query(
    "SELECT c.id_carrera, c.nombre, c.duracion_semestres, c.modalidad,
            a.nombre AS area, u.nombre AS universidad, u.ciudad
     FROM carrera c
     INNER JOIN area a ON a.id_area = c.id_area
     INNER JOIN universidad u ON u.id_universidad = c.id_universidad
     ORDER BY c.nombre"
)->fetch_all(MYSQLI_ASSOC);

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

    <?php if (count($carreras) === 0): ?>
        <div class="vacio">
            <div class="vacio-icon" aria-hidden="true">+</div>
            <h3>Aún no hay carreras</h3>
            <p>Crea la primera carrera y asígnala a un área y una universidad.</p>
            <div class="vacio-acciones">
                <a class="btn btn-dorado" href="carrera_form.php">Nueva carrera</a>
            </div>
        </div>
    <?php else: ?>
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
    <?php endif; ?>
</main>

<?php require __DIR__ . "/../includes/footer.php"; ?>
