<?php
session_start();
require_once __DIR__ . "/../includes/conexion.php";
require_once __DIR__ . "/../includes/funciones.php";
enviarHeadersCache('privado');
requiereAdmin();

$id = (int) ($_GET["id"] ?? $_POST["id_carrera"] ?? 0);
$editando = $id > 0;
$error = "";
$carrera = [
    "nombre" => "",
    "id_area" => "",
    "duracion_semestres" => "8",
    "modalidad" => "Presencial",
    "descripcion" => "",
    "id_universidad" => "",
];
$antes = null;

if ($editando && $_SERVER["REQUEST_METHOD"] !== "POST") {
    $stmt = $mysqli->prepare("SELECT * FROM carrera WHERE id_carrera = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if (!$row) {
        guardarMensaje("Carrera no encontrada.");
        header("Location: carreras.php");
        exit;
    }
    $carrera = $row;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nombre = trim($_POST["nombre"] ?? "");
    $idArea = (int) ($_POST["id_area"] ?? 0);
    $duracion = (int) ($_POST["duracion_semestres"] ?? 0);
    $modalidad = trim($_POST["modalidad"] ?? "");
    $descripcion = trim($_POST["descripcion"] ?? "");
    $idUniversidad = (int) ($_POST["id_universidad"] ?? 0);

    $carrera = [
        "nombre" => $nombre,
        "id_area" => $idArea,
        "duracion_semestres" => $duracion,
        "modalidad" => $modalidad,
        "descripcion" => $descripcion,
        "id_universidad" => $idUniversidad,
    ];

    if ($nombre === "" || mb_strlen($nombre) > 200) {
        $error = "El nombre es obligatorio (máx. 200 caracteres).";
    } elseif ($idArea <= 0) {
        $error = "Debes elegir un área.";
    } elseif ($idUniversidad <= 0) {
        $error = "Debes elegir una universidad.";
    } elseif ($duracion < 1 || $duracion > 20) {
        $error = "La duración debe estar entre 1 y 20 semestres.";
    } elseif ($modalidad === "" || mb_strlen($modalidad) > 80) {
        $error = "La modalidad es obligatoria.";
    } else {
        try {
            if ($editando) {
                $antes = cargarCarreraCompleta($mysqli, $id);
                $stmt = $mysqli->prepare(
                    "UPDATE carrera
                     SET nombre=?, id_area=?, duracion_semestres=?, modalidad=?, descripcion=?, id_universidad=?
                     WHERE id_carrera=?"
                );
                $stmt->bind_param("siissii", $nombre, $idArea, $duracion, $modalidad, $descripcion, $idUniversidad, $id);
                $stmt->execute();
                cacheParcheCarrera($mysqli, $id, $antes);
                guardarMensaje("Carrera actualizada.");
            } else {
                $stmt = $mysqli->prepare(
                    "INSERT INTO carrera (nombre, id_area, duracion_semestres, modalidad, descripcion, id_universidad)
                     VALUES (?, ?, ?, ?, ?, ?)"
                );
                $stmt->bind_param("siissi", $nombre, $idArea, $duracion, $modalidad, $descripcion, $idUniversidad);
                $stmt->execute();
                $nuevoId = (int) $mysqli->insert_id;
                cacheParcheCarrera($mysqli, $nuevoId);
                guardarMensaje("Carrera creada.");
            }
            header("Location: carreras.php");
            exit;
        } catch (mysqli_sql_exception $e) {
            $error = "No se pudo guardar la carrera.";
        }
    }
}

$areas = obtenerAreas($mysqli);
$universidades = $mysqli->query("SELECT id_universidad, nombre, ciudad FROM universidad ORDER BY nombre")->fetch_all(MYSQLI_ASSOC);

require __DIR__ . "/../includes/header_admin.php";
?>

<main class="contenedor">
    <div class="page-heading">
        <div>
            <h1><?= $editando ? "Editar carrera" : "Nueva carrera" ?></h1>
            <p>Asocia la carrera a un área y una universidad.</p>
        </div>
        <a class="btn btn-outline" href="carreras.php">← Volver</a>
    </div>

    <section class="panel">
        <?php if ($error !== ""): ?>
            <p class="form-error"><?= e($error) ?></p>
        <?php endif; ?>

        <form method="post" class="form-grid">
            <?php if ($editando): ?>
                <input type="hidden" name="id_carrera" value="<?= $id ?>">
            <?php endif; ?>

            <div class="campo ancho-completo">
                <label for="nombre">Nombre de la carrera</label>
                <input type="text" id="nombre" name="nombre" required maxlength="200" value="<?= e($carrera["nombre"]) ?>">
            </div>
            <div class="campo">
                <label for="id_area">Área</label>
                <select id="id_area" name="id_area" required>
                    <option value="">Selecciona un área</option>
                    <?php foreach ($areas as $area): ?>
                        <option value="<?= (int) $area["id_area"] ?>" <?= (int) $carrera["id_area"] === (int) $area["id_area"] ? "selected" : "" ?>>
                            <?= e($area["nombre"]) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="campo">
                <label for="id_universidad">Universidad</label>
                <select id="id_universidad" name="id_universidad" required>
                    <option value="">Selecciona una universidad</option>
                    <?php foreach ($universidades as $u): ?>
                        <option value="<?= (int) $u["id_universidad"] ?>" <?= (int) $carrera["id_universidad"] === (int) $u["id_universidad"] ? "selected" : "" ?>>
                            <?= e($u["nombre"]) ?> (<?= e($u["ciudad"]) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="campo">
                <label for="duracion_semestres">Duración (semestres)</label>
                <input type="number" id="duracion_semestres" name="duracion_semestres" min="1" max="20" required value="<?= e((string) $carrera["duracion_semestres"]) ?>">
            </div>
            <div class="campo">
                <label for="modalidad">Modalidad</label>
                <input type="text" id="modalidad" name="modalidad" required maxlength="80" value="<?= e($carrera["modalidad"]) ?>" placeholder="Presencial, Virtual, Híbrida...">
            </div>
            <div class="campo ancho-completo">
                <label for="descripcion">Descripción</label>
                <textarea id="descripcion" name="descripcion"><?= e($carrera["descripcion"] ?? "") ?></textarea>
            </div>
            <div class="ancho-completo">
                <button class="btn btn-dorado" type="submit">Guardar</button>
            </div>
        </form>
    </section>
</main>

<?php require __DIR__ . "/../includes/footer.php"; ?>
