<?php
session_start();
require_once __DIR__ . "/../includes/conexion.php";
require_once __DIR__ . "/../includes/funciones.php";

if (adminLogueado()) {
    header("Location: index.php");
    exit;
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $correo = trim($_POST["correo"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($correo === "" || $password === "") {
        $error = "Ingresa correo y contraseña.";
    } else {
        $stmt = $mysqli->prepare("SELECT id_admin, nombre, correo, password_hash FROM admin WHERE correo = ? LIMIT 1");
        $stmt->bind_param("s", $correo);
        $stmt->execute();
        $admin = $stmt->get_result()->fetch_assoc();

        if ($admin && password_verify($password, $admin["password_hash"])) {
            $_SESSION["admin"] = [
                "id_admin" => (int) $admin["id_admin"],
                "nombre" => $admin["nombre"],
                "correo" => $admin["correo"],
            ];
            header("Location: index.php");
            exit;
        }
        $error = "Correo o contraseña incorrectos.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ingresar — ExpoFuturo</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;600;700&family=Libre+Baskerville:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/estilos.css">
</head>
<body>
<div class="login-wrap">
    <div class="login-card">
        <img src="../img/logo.png" alt="ExpoFuturo">
        <h1>Panel ExpoFuturo</h1>
        <p>Acceso exclusivo para administradores</p>

        <?php if ($error !== ""): ?>
            <p class="form-error"><?= e($error) ?></p>
        <?php endif; ?>

        <form method="post">
            <div class="campo">
                <label for="correo">Correo</label>
                <input type="email" id="correo" name="correo" required autocomplete="username" value="<?= e($_POST["correo"] ?? "") ?>">
            </div>
            <div class="campo">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" required autocomplete="current-password">
            </div>
            <button class="btn btn-dorado" type="submit">Ingresar</button>
        </form>
        <p style="margin-top:1.2rem;"><a href="../index.php">← Volver al sitio</a></p>
    </div>
</div>
</body>
</html>
