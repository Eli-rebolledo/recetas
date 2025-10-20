<?php
session_start();

function usuarioEstaLogeado() {
    return isset($_SESSION['usuario_id']);
}

function obtenerUsuarioId() {
    return $_SESSION['usuario_id'] ?? null;
}

function obtenerUsuarioNombre() {
    return $_SESSION['usuario_nombre'] ?? 'Invitado';
}
?>