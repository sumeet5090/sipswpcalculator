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

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

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

$router->get('/resources', 'BlogController@index');
$router->get('/resource', 'BlogController@index');
// Hierarchical Blog Route
$router->get('/resource/{category}/{slug}', 'BlogController@show');

// 301 Redirects for Legacy Flat URLs
$blog_redirects = [
    'sip-for-beginners' => 'growth',
    '20-year-wealth-blueprint-step-up-sip' => 'growth',
    'reach-1-million-dollar-1-crore-rupees-in-18-years' => 'growth',
    'inflation-impact-on-sip' => 'growth',
    'retirement-planning-4-percent-swp-rule' => 'retirement',
    'sip-vs-swp-wealth-creation-withdrawal-strategy' => 'retirement',
    'swp-retirement-planning' => 'retirement',
    'sip-vs-fd-vs-ppf' => 'comparison',
    'swp-vs-fixed-deposit' => 'comparison',
    'swp-vs-annuity-2026' => 'comparison',
    'mutual-fund-tax-2026' => 'comparison',
    'mf-returns-benchmarks' => 'comparison',
    // Consolidated / Redundant Posts (Redirected to Cornerstone Guides)
    'why-flat-sips-lose-money-stepup-sip-power' => 'growth/20-year-wealth-blueprint-step-up-sip',
    'mathematics-of-4-percent-rule-swp' => 'retirement/retirement-planning-4-percent-swp-rule',
    'sip-to-swp-transition-guide' => 'retirement/sip-vs-swp-wealth-creation-withdrawal-strategy'
];

foreach ($blog_redirects as $slug => $target) {
    if (strpos($target, '/') !== false) {
        // Direct mapping for consolidated posts
        $router->redirect("/resource/{$slug}", "/resource/{$target}");
    } else {
        // Standard mapping for active posts
        $router->redirect("/resource/{$slug}", "/resource/{$target}/{$slug}");
    }
}

$stubs = [
    '/20-year-wealth-blueprint-step-up-sip' => '/resource/growth/20-year-wealth-blueprint-step-up-sip',
    '/inflation-impact-on-sip' => '/resource/growth/inflation-impact-on-sip',
    '/mathematics-of-4-percent-rule-swp' => '/resource/retirement/retirement-planning-4-percent-swp-rule',
    '/mf-returns-benchmarks' => '/resource/comparison/mf-returns-benchmarks',
    '/mutual-fund-tax-2026' => '/resource/comparison/mutual-fund-tax-2026',
    '/reach-1-million-dollar-1-crore-rupees-in-18-years' => '/resource/growth/reach-1-million-dollar-1-crore-rupees-in-18-years',
    '/retirement-planning-4-percent-swp-rule' => '/resource/retirement/retirement-planning-4-percent-swp-rule',
    '/sip-for-beginners' => '/resource/growth/sip-for-beginners',
    '/sip-to-swp-transition-guide' => '/resource/retirement/sip-vs-swp-wealth-creation-withdrawal-strategy',
    '/sip-vs-fd-vs-ppf' => '/resource/comparison/sip-vs-fd-vs-ppf',
    '/sip-vs-swp-wealth-creation-withdrawal-strategy' => '/resource/retirement/sip-vs-swp-wealth-creation-withdrawal-strategy',
    '/swp-retirement-planning' => '/resource/retirement/swp-retirement-planning',
    '/swp-vs-annuity-2026' => '/resource/comparison/swp-vs-annuity-2026',
    '/swp-vs-fixed-deposit' => '/resource/comparison/swp-vs-fixed-deposit',
    '/why-flat-sips-lose-money-stepup-sip-power' => '/resource/growth/20-year-wealth-blueprint-step-up-sip',
    '/earning-30k-at-25-investment-blueprint' => '/resource/growth/earning-30k-at-25-investment-blueprint'
];

foreach ($stubs as $old => $new) {
    $router->redirect($old, $new);
    $router->redirect($old . '.php', $new);
}

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];
$router->dispatch($uri, $method);
