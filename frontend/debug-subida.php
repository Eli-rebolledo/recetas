<?php
require_once __DIR__ . '/../backend/session.php';
require_once __DIR__ . '/../backend/conexion.php';
require_once 'helpers.php';

echo "<pre>";
echo "=== DEBUG SUBIDA DE ARCHIVOS ===\n\n";

// Verificar configuración de PHP
echo "1. CONFIGURACIÓN PHP:\n";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "\n";
echo "post_max_size: " . ini_get('post_max_size') . "\n";
echo "max_file_uploads: " . ini_get('max_file_uploads') . "\n\n";

// Verificar si hay archivos en $_FILES
echo "2. ARCHIVOS EN \$_FILES:\n";
print_r($_FILES);
echo "\n";

// Verificar si hay datos en $_POST
echo "3. DATOS EN \$_POST:\n";
print_r($_POST);
echo "\n";

// Verificar permisos de carpeta
echo "4. PERMISOS DE CARPETA img/:\n";
$carpeta_img = __DIR__ . '/../img/';
echo "Ruta: " . $carpeta_img . "\n";
echo "Existe: " . (file_exists($carpeta_img) ? 'SÍ' : 'NO') . "\n";
echo "Es escribible: " . (is_writable($carpeta_img) ? 'SÍ' : 'NO') . "\n";
echo "Permisos: " . substr(sprintf('%o', fileperms($carpeta_img)), -4) . "\n\n";

// Probar crear un archivo de prueba
echo "5. PRUEBA DE ESCRITURA:\n";
$archivo_prueba = $carpeta_img . 'test.txt';
if (file_put_contents($archivo_prueba, 'test')) {
    echo "✓ Se pudo crear archivo de prueba\n";
    unlink($archivo_prueba);
} else {
    echo "✗ NO se pudo crear archivo de prueba\n";
}

echo "</pre>";
?>