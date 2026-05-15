<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Artisan Helper</h2>";

function runArtisan($command) {
    echo "Running: php artisan $command...<br>";
    // Since we don't have shell_exec, we have to use a different way to run Laravel commands
    // We can try to include artisan or use a web-based command runner logic
    
    // Fallback: try to trigger artisan via include
    $_SERVER['argv'] = explode(' ', 'artisan ' . $command);
    
    ob_start();
    try {
        include __DIR__ . '/../tsbl-invoice-laravel/artisan';
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "<br>";
    }
    $output = ob_get_clean();
    echo "<pre>$output</pre><hr>";
}

$cmd = $_GET['cmd'] ?? '';
if ($cmd) {
    runArtisan($cmd);
} else {
    echo "Usage: artisan_helper.php?cmd=migrate<br>";
    echo "Common commands: <br>";
    echo "<li><a href='?cmd=migrate --force'>migrate --force</a></li>";
    echo "<li><a href='?cmd=view:clear'>view:clear</a></li>";
    echo "<li><a href='?cmd=cache:clear'>cache:clear</a></li>";
    echo "<li><a href='?cmd=route:clear'>route:clear</a></li>";
    echo "<li><a href='?cmd=config:clear'>config:clear</a></li>";
}
