<?php
session_start();
require_once __DIR__ . "/../includes/conexion.php";
require_once __DIR__ . "/../includes/funciones.php";
requiereAdmin();

$id = (int) ($_GET["id"] ?? $_POST["id_universidad"] ?? 0);
$editando = $id > 0;
$error = "";
$universidad = [
    "nombre" => "",
    "ciudad" => "",
    "tipo" => "publica",
    "sitio_web" => "",
    "descripcion" => "",
    "logo_url" => "",
];

if ($editando && $_SERVER["REQUEST_METHOD"] !== "POST") {
    $stmt = $mysqli->prepare("SELECT * FROM universidad WHERE id_universidad = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if (!$row) {
        guardarMensaje("Universidad no encontrada.");
        header("Location: universidades.php");
        exit;
    }
    $universidad = $row;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nombre = trim($_POST["nombre"] ?? "");
    $ciudad = trim($_POST["ciudad"] ?? "");
    $tipo = trim($_POST["tipo"] ?? "");
    $sitioWeb = trim($_POST["sitio_web"] ?? "");
    $descripcion = trim($_POST["descripcion"] ?? "");
    $logoUrl = trim($_POST["logo_url"] ?? "");

    $universidad = [
        "nombre" => $nombre,
        "ciudad" => $ciudad,
        "tipo" => $tipo,
        "sitio_web" => $sitioWeb,
        "descripcion" => $descripcion,
        "logo_url" => $logoUrl,
    ];

    if ($nombre === "" || mb_strlen($nombre) > 200) {
        $error = "El nombre es obligatorio (máx. 200 caracteres).";
    } elseif ($ciudad === "" || mb_strlen($ciudad) > 100) {
        $error = "La ciudad es obligatoria (máx. 100 caracteres).";
    } elseif ($tipo !== "publica" && $tipo !== "privada") {
        $error = "Selecciona un tipo válido (pública o privada).";
    } else {
        try {
            if ($editando) {
                $stmt = $mysqli->prepare(
                    "UPDATE universidad
                     SET nombre=?, ciudad=?, tipo=?, sitio_web=?, descripcion=?, logo_url=?
                     WHERE id_universidad=?"
                );
                $stmt->bind_param("ssssssi", $nombre, $ciudad, $tipo, $sitioWeb, $descripcion, $logoUrl, $id);
                $stmt->execute();
                guardarMensaje("Universidad actualizada.");
            } else {
                $stmt = $mysqli->prepare(
                    "INSERT INTO universidad (nombre, ciudad, tipo, sitio_web, descripcion, logo_url)
                     VALUES (?, ?, ?, ?, ?, ?)"
                );
                $stmt->bind_param("ssssss", $nombre, $ciudad, $tipo, $sitioWeb, $descripcion, $logoUrl);
                $stmt->execute();
                guardarMensaje("Universidad creada.");
            }
            header("Location: universidades.php");
            exit;
        } catch (mysqli_sql_exception $e) {
            $error = "No se pudo guardar la universidad.";
        }
    }
}

require __DIR__ . "/../includes/header_admin.php";
?>

<main class="contenedor">
    <div class="page-heading">
        <div>
            <h1><?= $editando ? "Editar universidad" : "Nueva universidad" ?></h1>
            <p>Completa la información de la institución.</p>
        </div>
        <a class="btn btn-outline" href="universidades.php">← Volver</a>
    </div>

    <section class="panel">
        <?php if ($error !== ""): ?>
            <p class="form-error"><?= e($error) ?></p>
        <?php endif; ?>

        <form method="post" class="form-grid">
            <?php if ($editando): ?>
                <input type="hidden" name="id_universidad" value="<?= $id ?>">
            <?php endif; ?>

            <div class="campo ancho-completo">
                <label for="nombre">Nombre</label>
                <input type="text" id="nombre" name="nombre" required maxlength="200" value="<?= e($universidad["nombre"]) ?>">
            </div>
            <div class="campo">
                <label for="ciudad">Ciudad</label>
                <input type="text" id="ciudad" name="ciudad" required maxlength="100" value="<?= e($universidad["ciudad"]) ?>">
            </div>
            <div class="campo">
                <label for="tipo">Tipo</label>
                <select id="tipo" name="tipo" required>
                    <option value="publica" <?= $universidad["tipo"] === "publica" ? "selected" : "" ?>>Pública</option>
                    <option value="privada" <?= $universidad["tipo"] === "privada" ? "selected" : "" ?>>Privada</option>
                </select>
            </div>
            <div class="campo">
                <label for="sitio_web">Sitio web</label>
                <input type="url" id="sitio_web" name="sitio_web" maxlength="255" placeholder="https://" value="<?= e($universidad["sitio_web"] ?? "") ?>">
            </div>
            <div class="campo">
                <label for="logo_url">URL del logo (opcional)</label>
                <input type="text" id="logo_url" name="logo_url" maxlength="255" value="<?= e($universidad["logo_url"] ?? "") ?>">
            </div>
            <div class="campo ancho-completo">
                <label for="descripcion">Descripción</label>
                <textarea id="descripcion" name="descripcion"><?= e($universidad["descripcion"] ?? "") ?></textarea>
            </div>
            <div class="ancho-completo">
                <button class="btn btn-dorado" type="submit">Guardar</button>
            </div>
        </form>
    </section>
</main>

<?php require __DIR__ . "/../includes/footer.php"; ?>
