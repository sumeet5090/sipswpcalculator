<?php
declare(strict_types=1);

if (php_sapi_name() === 'cli-server') {
    if (is_file(__DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH))) {
        return false;
    }
}

spl_autoload_register(function ($class) {
    if (strpos($class, '\\') !== false) {
        $path = __DIR__ . '/src/' . str_replace('\\', '/', $class) . '.php';
        if (file_exists($path)) {
            require_once $path;
        }
    }
});

require_once __DIR__ . '/functions.php';

$router = new \Core\Router();
$router->get('/', 'CalculatorController@home');
$router->post('/', 'CalculatorController@home');

$calculators = [
    '/compound-interest-calculator',
    '/dollar-cost-averaging-tool',
    '/recurring-investment-calculator',
    '/retirement-drawdown-planner',
    '/sip-calculator',
    '/sip-step-up-calculator',
    '/swp-tax-calculator'
];

foreach ($calculators as $calc) {
    $slugParts = explode('-', ltrim($calc, '/'));
    $methodName = array_shift($slugParts);
    foreach ($slugParts as $part) {
        $methodName .= ucfirst($part);
    }
    
    $router->get($calc, "CalculatorController@{$methodName}");
    $router->post($calc, "CalculatorController@{$methodName}");
    $router->redirect($calc . '.php', $calc);
}

$pages = [
    '/about' => 'about',
    '/faq' => 'faq',
    '/glossary' => 'glossary',
    '/privacy' => 'privacy',
    '/resources' => 'resources',
    '/terms' => 'terms'
];

foreach ($pages as $uri => $method) {
    $router->get($uri, "PageController@{$method}");
    $router->redirect($uri . '.php', $uri);
}

$router->get('/admin_insights', 'AdminController@insights');
$router->post('/admin_insights', 'AdminController@insights');
$router->redirect('/admin_insights.php', '/admin_insights');
$router->get('/log_insight', 'AdminController@logInsight');
$router->post('/log_insight', 'AdminController@logInsight');
$router->redirect('/log_insight.php', '/log_insight');

$router->get('/resource', 'BlogController@index');
$router->get('/resource/{slug}', 'BlogController@show');

$stubs = [
    '/20-year-wealth-blueprint-step-up-sip' => '/resource/20-year-wealth-blueprint-step-up-sip',
    '/inflation-impact-on-sip' => '/resource/inflation-impact-on-sip',
    '/mathematics-of-4-percent-rule-swp' => '/resource/mathematics-of-4-percent-rule-swp',
    '/mf-returns-benchmarks' => '/resource/mf-returns-benchmarks',
    '/mutual-fund-tax-2026' => '/resource/mutual-fund-tax-2026',
    '/reach-1-million-dollar-1-crore-rupees-in-18-years' => '/resource/reach-1-million-dollar-1-crore-rupees-in-18-years',
    '/retirement-planning-4-percent-swp-rule' => '/resource/retirement-planning-4-percent-swp-rule',
    '/sip-for-beginners' => '/resource/sip-for-beginners',
    '/sip-to-swp-transition-guide' => '/resource/sip-to-swp-transition-guide',
    '/sip-vs-fd-vs-ppf' => '/resource/sip-vs-fd-vs-ppf',
    '/sip-vs-swp-wealth-creation-withdrawal-strategy' => '/resource/sip-vs-swp-wealth-creation-withdrawal-strategy',
    '/swp-retirement-planning' => '/resource/swp-retirement-planning',
    '/swp-vs-annuity-2026' => '/resource/swp-vs-annuity-2026',
    '/swp-vs-fixed-deposit' => '/resource/swp-vs-fixed-deposit',
    '/why-flat-sips-lose-money-stepup-sip-power' => '/resource/why-flat-sips-lose-money-stepup-sip-power'
];

foreach ($stubs as $old => $new) {
    $router->redirect($old, $new);
    $router->redirect($old . '.php', $new);
}

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];
$router->dispatch($uri, $method);
