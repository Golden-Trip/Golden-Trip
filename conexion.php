<?php
$host = "sql102.infinityfree.com";
$usuario = "if0_39332117";
$clave = "8GnJOVbkacdEvV4";
$base = "if0_39332117_goldentrip";

$conexion = new mysqli($host, $usuario, $clave, $base);

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// *** ¡AÑADÍ ESTA LÍNEA! ***
$conexion->set_charset("utf8mb4");
// *************************

?>