<?php
$host = "localhost:3307";
$usuario = "root";
$password = "";
$base_datos = "recepapp";

$conexion = new mysqli($host, $usuario, $password, $base_datos);

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}
?>