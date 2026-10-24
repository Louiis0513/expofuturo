<?php
session_start();
require_once __DIR__ . "/../includes/funciones.php";

unset($_SESSION["admin"]);
guardarMensaje("Sesión cerrada correctamente.");
header("Location: login.php");
exit;
