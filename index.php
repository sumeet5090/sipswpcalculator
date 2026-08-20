<?php
declare(strict_types=1);

if (php_sapi_name() === 'cli-server') {
    $rawPath = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    $filePath = __DIR__ . $rawPath;

    // Only allow specific static asset extensions from safe public directories
    $allowedExts = ['css', 'js', 'jpg', 'jpeg', 'png', 'gif', 'svg', 'webp', 'ico', 'woff', 'woff2', 'ttf', 'json', 'map'];
    $ext = strtolower((string) pathinfo($rawPath, PATHINFO_EXTENSION));

    if (in_array($ext, $allowedExts, true) && is_file($filePath)) {
        // Block sensitive directories even if extension matches (e.g. content/*.json, database/*.json)
        if (!preg_match('#^/(content|database|var|src|tests|bin|scripts|\.git)#', $rawPath)) {
            return false;
        }
    }
}

require_once __DIR__ . '/vendor/autoload.php';

// Discover and load .env from current directory or parent release directories
$envDirs = array_filter([
    __DIR__,
    dirname(__DIR__),
    dirname(__DIR__, 2),
], 'is_dir');

foreach ($envDirs as $envDir) {
    if (file_exists($envDir . '/.env')) {
        $dotenv = \Dotenv\Dotenv::createImmutable($envDir);
        $dotenv->safeLoad();
        break;
    }
}

try {
    $request = \Core\Http\Request::createFromGlobals();
    $app = new \Core\App();
    $app->run($request);
} catch (\Throwable $e) {
    // If the autoloader or App container fails, this is our absolute last line of defense.
    if (class_exists('\Controllers\ErrorController')) {
        $response = \Controllers\ErrorController::handle500($e);
        $response->send();
    } else {
        http_response_code(500);
        error_log("Fatal Bootstrap Error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
        echo "500 Internal Server Error";
    }
}

