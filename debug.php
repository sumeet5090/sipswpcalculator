<?php
require_once __DIR__ . '/index.php';

echo "Autoloader and index.php loaded successfully.\n";

try {
    $controller = new \Controllers\CalculatorController();
    echo "CalculatorController loaded successfully.\n";
} catch (\Throwable $e) {
    echo "Error loading CalculatorController: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
