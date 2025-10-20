<?php
$frontend_files = [
    'frontend/index.php',
    'frontend/registrarse.php',
    'frontend/iniciar-sesion.php',
    'frontend/ver-mas-recetas.php',
    'frontend/ver-receta.php',
    'frontend/ver-recetas-propias.php',
    'frontend/ver-favoritos.php',
    'frontend/agregar-receta.php',
    'frontend/editar-receta.php'
];

foreach ($frontend_files as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        
        // Reemplazar rutas de backend
        $content = str_replace(
            "require_once 'backend/",
            "require_once __DIR__ . '/../backend/",
            $content
        );
        
        // Reemplazar rutas de imágenes
        $content = str_replace(
            "src=\"img/",
            "src=\"../img/",
            $content
        );
        
        // Reemplazar rutas de logout
        $content = str_replace(
            "href=\"backend/logout.php",
            "href=\"../backend/logout.php",
            $content
        );
        
        file_put_contents($file, $content);
        echo "Corregido: $file\n";
    } else {
        echo "No existe: $file\n";
    }
}

echo "¡Proceso completado!\n";
?>