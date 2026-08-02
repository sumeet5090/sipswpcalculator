<?php
declare(strict_types=1);

if (php_sapi_name() === 'cli-server') {
    if (is_file(__DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH))) {
        return false;
    }
}

require_once __DIR__ . '/vendor/autoload.php';

// Simple .env parser to load environment variables
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            putenv(trim($name) . '=' . trim($value));
            $_ENV[trim($name)] = trim($value);
        }
    }
}

try {
    $app = new \Core\App();
    $app->run();
} catch (\Throwable $e) {
    // If the autoloader or App container fails, this is our absolute last line of defense.
    if (class_exists('\Controllers\ErrorController')) {
        \Controllers\ErrorController::handle500($e);
    } else {
        http_response_code(500);
        echo "Fatal Error: " . $e->getMessage();
    }
}
