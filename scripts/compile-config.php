<?php

declare(strict_types=1);

$configPath = __DIR__ . '/../src/Core/Config/calculator_defaults.php';
$outputPath = __DIR__ . '/../assets/js/calculators/calculator_defaults.json';

if (!file_exists($configPath)) {
    fwrite(STDERR, "Error: Configuration file not found at $configPath\n");
    exit(1);
}

$config = require $configPath;

$json = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

if ($json === false) {
    fwrite(STDERR, "Error: Failed to encode configuration to JSON: " . json_last_error_msg() . "\n");
    exit(1);
}

// Ensure target directory exists
$dir = dirname($outputPath);
if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
}

if (file_put_contents($outputPath, $json) === false) {
    fwrite(STDERR, "Error: Failed to write JSON to $outputPath\n");
    exit(1);
}

echo "Success: Compiled configuration to $outputPath\n";
