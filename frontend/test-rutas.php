<?php
echo "<pre>";
echo "=== DEBUG DE RUTAS ===\n\n";

echo "1. __DIR__: " . __DIR__ . "\n";
echo "2. dirname(__DIR__): " . dirname(__DIR__) . "\n";

$ruta_img = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'img';
echo "3. Ruta img: " . $ruta_img . "\n";
echo "4. Existe carpeta: " . (is_dir($ruta_img) ? 'SÍ' : 'NO') . "\n";
echo "5. Es escribible: " . (is_writable($ruta_img) ? 'SÍ' : 'NO') . "\n";

// Probar crear archivo
$test_file = $ruta_img . DIRECTORY_SEPARATOR . 'test.txt';
if (file_put_contents($test_file, 'test')) {
    echo "6. ✓ Se pudo crear archivo de prueba\n";
    unlink($test_file);
} else {
    echo "6. ✗ NO se pudo crear archivo de prueba\n";
}

echo "</pre>";
?>