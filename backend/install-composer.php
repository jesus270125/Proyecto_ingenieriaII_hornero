<?php
echo "Descargando Composer...\n";
$installer_url = 'https://getcomposer.org/installer';
$installer_file = 'composer-setup.php';

if (copy($installer_url, $installer_file)) {
    echo "✓ Instalador descargado\n";
    echo "Ejecutando instalador...\n";
    
    $output = shell_exec('php ' . escapeshellarg($installer_file) . ' 2>&1');
    echo $output;
    
    if (file_exists('composer.phar')) {
        echo "✓ Composer instalado como composer.phar\n";
    }
    
    // Eliminar instalador
    unlink($installer_file);
    echo "✓ Instalador eliminado\n";
} else {
    echo "✗ Error descargando instalador\n";
}
?>
