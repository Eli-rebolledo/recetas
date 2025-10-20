<?php
function url($path = '') {
    $base_url = '/recetas/frontend';
    return $base_url . ($path ? '/' . ltrim($path, '/') : '');
}

function asset($path) {
    return '/recetas/frontend/' . ltrim($path, '/');
}
?>