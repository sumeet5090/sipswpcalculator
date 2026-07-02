<?php
declare(strict_types=1);
session_start();

$envPassword = getenv('ADMIN_INSIGHTS_PASSWORD');
$ADMIN_PASSWORD = ($envPassword !== false && $envPassword !== '') ? $envPassword : 'sipswp_admin_2026!';

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: /admin_insights');
    exit;
}

// Handle login attempt
$loginError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    if (hash_equals($ADMIN_PASSWORD, $_POST['password'])) {
        $_SESSION['admin_authenticated'] = true;
    } else {
        $loginError = 'Incorrect password. Access denied.';
    }
}

// If not authenticated, show login form and exit
if (empty($_SESSION['admin_authenticated'])) {
    showLoginPage($loginError);
    exit;
}

// ─────────────────────────────────────────────────────────────
// 2. DATABASE CONNECTION & SCHEMA VALIDATION
// ─────────────────────────────────────────────────────────────
require_once __DIR__ . '/../../AnonymizedInsightLogger.php';
$dbPath = __DIR__ . '/../../../database/database.sqlite';

if (!file_exists($dbPath)) {
    die('Database not found at: ' . $dbPath);
}

// ── SCHEMA VALIDATION: Instantiate logger to ensure required columns exist ──
try {
    $logger = new AnonymizedInsightLogger($dbPath);
} catch (\Throwable $e) {
    error_log("Admin Insights Schema Validation Error: " . $e->getMessage());
}

$pdo = new PDO('sqlite:' . $dbPath, null, null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

// Performance indexes (idempotent — no impact if already exist)
$pdo->exec("CREATE INDEX IF NOT EXISTS idx_calc_created_at ON user_calculations(created_at)");
$pdo->exec("CREATE INDEX IF NOT EXISTS idx_calc_currency ON user_calculations(currency)");
$pdo->exec("CREATE INDEX IF NOT EXISTS idx_calc_type ON user_calculations(calc_type)");

// ─────────────────────────────────────────────────────────────
// 3. TIME RANGE FILTERING
// ─────────────────────────────────────────────────────────────
$time_ranges = [
    '24h' => ['label' => '24 Hours', 'interval' => '-1 day', 'chart_days' => 1],
    '48h' => ['label' => '48 Hours', 'interval' => '-2 days', 'chart_days' => 2],
    '72h' => ['label' => '72 Hours', 'interval' => '-3 days', 'chart_days' => 3],
    '1w' => ['label' => '1 Week', 'interval' => '-7 days', 'chart_days' => 7],
    '1m' => ['label' => '1 Month', 'interval' => '-30 days', 'chart_days' => 30],
    '6m' => ['label' => '6 Months', 'interval' => '-180 days', 'chart_days' => 180],
    '1y' => ['label' => '1 Year', 'interval' => '-365 days', 'chart_days' => 365],
];

$current_range_key = $_GET['range'] ?? '24h';
if (!isset($time_ranges[$current_range_key])) {
    $current_range_key = '1m';
}
$current_range = $time_ranges[$current_range_key];
$current_interval = $current_range['interval'];

// ─────────────────────────────────────────────────────────────
// 4. DATA AGGREGATION QUERIES (Filtered by selected range)
// ─────────────────────────────────────────────────────────────
$where_clause = "WHERE created_at >= datetime('now', :interval)";
$params = [':interval' => $current_interval];

// -- KPI: Total calculations in selected range ----
$stmt = $pdo->prepare("SELECT COUNT(*) FROM user_calculations $where_clause");
$stmt->execute($params);
$totalInRange = (int) $stmt->fetchColumn();

// -- KPI: Average Step-Up % in range ----
$stmt = $pdo->prepare("SELECT COALESCE(AVG(step_up_pct), 0) FROM user_calculations $where_clause AND step_up_pct > 0");
$stmt->execute($params);
$avgStepUp = (float) $stmt->fetchColumn();

// -- KPI: Total all-time (unfiltered) ----
$totalAllTime = (int) $pdo->query("SELECT COUNT(*) FROM user_calculations")->fetchColumn();

// -- KPI: Calculations by type in range ----
$stmt = $pdo->prepare("SELECT calc_type, COUNT(*) AS cnt FROM user_calculations $where_clause GROUP BY calc_type ORDER BY cnt DESC");
$stmt->execute($params);
$calcTypeBreakdown = $stmt->fetchAll();

// -- KPI: PDF Downloads & Conversion Rate in range ----
$totalPdfDownloads = 0;
$conversionRate = 0.0;
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_calculations $where_clause AND pdf_downloaded = 1");
    $stmt->execute($params);
    $totalPdfDownloads = (int) $stmt->fetchColumn();
    $conversionRate = $totalInRange > 0 ? round(($totalPdfDownloads / $totalInRange) * 100, 1) : 0.0;
} catch (\Throwable $e) {
    error_log("Query Error (pdf_downloaded): " . $e->getMessage());
}

// -- Table: Top 10 Referrers in range ----
$topReferrers = [];
try {
    $stmt = $pdo->prepare("
        SELECT
            CASE
                WHEN referrer IS NULL OR referrer = '' THEN '(direct / unknown)'
                ELSE SUBSTR(referrer, 1, 80)
            END AS source,
            COUNT(*) AS cnt
        FROM user_calculations
        $where_clause
        GROUP BY source
        ORDER BY cnt DESC
        LIMIT 10
    ");
    $stmt->execute($params);
    $topReferrers = $stmt->fetchAll();
} catch (\Throwable $e) {
    error_log("Query Error (referrer): " . $e->getMessage());
}

// -- Chart: Calculation volume in range ----
$stmt = $pdo->prepare("
    SELECT DATE(created_at) AS day, COUNT(*) AS cnt
    FROM user_calculations
    $where_clause
    GROUP BY DATE(created_at)
    ORDER BY day ASC
");
$stmt->execute($params);
$dailyVolume = $stmt->fetchAll();

// -- Chart: Currency distribution in range ----
$stmt = $pdo->prepare("
    SELECT UPPER(COALESCE(currency, 'UNKNOWN')) AS currency, COUNT(*) AS cnt
    FROM user_calculations
    $where_clause
    GROUP BY UPPER(COALESCE(currency, 'UNKNOWN'))
    ORDER BY cnt DESC
");
$stmt->execute($params);
$currencyDist = $stmt->fetchAll();

// -- Table: Top 10 SWP target corpus amounts in range ----
$stmt = $pdo->prepare("
    SELECT amount, UPPER(COALESCE(currency, 'INR')) AS currency, COUNT(*) AS frequency
    FROM user_calculations
    $where_clause AND calc_type = 'SWP' AND amount IS NOT NULL
    GROUP BY amount, UPPER(COALESCE(currency, 'INR'))
    ORDER BY frequency DESC
    LIMIT 10
");
$stmt->execute($params);
$topCorpus = $stmt->fetchAll();

// -- KPI: Step-Up Adoption Rate in range ----
$stmt = $pdo->prepare("SELECT COUNT(*) FROM user_calculations $where_clause AND calc_type = 'SIP'");
$stmt->execute($params);
$totalSIP = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM user_calculations $where_clause AND calc_type = 'SIP' AND step_up_pct > 0");
$stmt->execute($params);
$stepUpSIP = (int) $stmt->fetchColumn();

$flatSIP = $totalSIP - $stepUpSIP;
$stepUpAdoptionRate = $totalSIP > 0 ? round(($stepUpSIP / $totalSIP) * 100, 1) : 0.0;

// -- KPI: Average Plan Duration in range ----
$stmt = $pdo->prepare("SELECT COALESCE(AVG(duration), 0) FROM user_calculations $where_clause AND calc_type = 'SIP'");
$stmt->execute($params);
$avgDurationSIP = (float) $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COALESCE(AVG(duration), 0) FROM user_calculations $where_clause AND calc_type = 'SWP'");
$stmt->execute($params);
$avgDurationSWP = (float) $stmt->fetchColumn();

// -- KPI: Average Interest Rate in range ----
$stmt = $pdo->prepare("SELECT COALESCE(AVG(interest_rate), 0) FROM user_calculations $where_clause AND interest_rate > 0");
$stmt->execute($params);
$avgInterestRate = (float) $stmt->fetchColumn();

// -- KPI: SWP Adoption Rate in range ----
$stmt = $pdo->prepare("SELECT COUNT(*) FROM user_calculations $where_clause AND swp_enabled = 1");
$stmt->execute($params);
$totalSWPEnabled = (int) $stmt->fetchColumn();
$swpAdoptionRate = $totalInRange > 0 ? round(($totalSWPEnabled / $totalInRange) * 100, 1) : 0.0;

// -- KPI: Average SIP Amount in range ----
$stmt = $pdo->prepare("SELECT COALESCE(AVG(sip_amount), 0) FROM user_calculations $where_clause AND sip_amount > 0");
$stmt->execute($params);
$avgSipAmount = (float) $stmt->fetchColumn();

// -- KPI: Average SWP Withdrawal in range ----
$stmt = $pdo->prepare("SELECT COALESCE(AVG(swp_withdrawal), 0) FROM user_calculations $where_clause AND swp_withdrawal > 0");
$stmt->execute($params);
$avgSwpWithdrawal = (float) $stmt->fetchColumn();

// -- Chart: Duration Distribution (histogram buckets) in range ----
$stmt = $pdo->prepare("
    SELECT
        CASE
            WHEN duration <= 1 THEN '1 yr'
            WHEN duration <= 3 THEN '2–3 yrs'
            WHEN duration <= 5 THEN '4–5 yrs'
            WHEN duration <= 10 THEN '6–10 yrs'
            WHEN duration <= 20 THEN '11–20 yrs'
            ELSE '20+ yrs'
        END AS bucket,
        COUNT(*) AS cnt
    FROM user_calculations
    $where_clause AND duration IS NOT NULL
    GROUP BY bucket
    ORDER BY MIN(duration) ASC
");
$stmt->execute($params);
$durationDist = $stmt->fetchAll();

// -- Corpus Buckets in range ----
$stmt = $pdo->prepare("
    SELECT
        CASE
            WHEN amount < 1000000 THEN 'Under 10L'
            WHEN amount < 5000000 THEN '10L – 50L'
            WHEN amount < 10000000 THEN '50L – 1Cr'
            ELSE 'Above 1Cr'
        END AS bucket,
        COUNT(*) AS cnt
    FROM user_calculations
    $where_clause AND calc_type = 'SWP' AND amount IS NOT NULL AND UPPER(COALESCE(currency,'INR')) = 'INR'
    GROUP BY bucket
    ORDER BY MIN(amount) ASC
");
$stmt->execute($params);
$corpusBucketsINR = $stmt->fetchAll();

$stmt = $pdo->prepare("
    SELECT
        CASE
            WHEN amount < 10000 THEN 'Under 10K'
            WHEN amount < 50000 THEN '10K – 50K'
            WHEN amount < 100000 THEN '50K – 100K'
            ELSE 'Above 100K'
        END AS bucket,
        COUNT(*) AS cnt
    FROM user_calculations
    $where_clause AND calc_type = 'SWP' AND amount IS NOT NULL AND UPPER(COALESCE(currency,'INR')) != 'INR'
    GROUP BY bucket
    ORDER BY MIN(amount) ASC
");
$stmt->execute($params);
$corpusBucketsUSD = $stmt->fetchAll();

// -- Ambition Index in range ----
$stmt = $pdo->prepare("
    SELECT
        CASE
            WHEN amount < 100000 THEN '$0 – 100K'
            WHEN amount < 500000 THEN '$100K – 500K'
            WHEN amount < 1000000 THEN '$500K – 1M'
            WHEN amount < 5000000 THEN '$1M – 5M'
            ELSE '$5M+'
        END AS goal_bucket,
        COUNT(*) AS cnt
    FROM user_calculations
    $where_clause AND amount IS NOT NULL
    GROUP BY goal_bucket
    ORDER BY MIN(amount) ASC
");
$stmt->execute($params);
$ambitionBuckets = $stmt->fetchAll();

// ─────────────────────────────────────────────────────────────
// 4. PREPARE JSON FOR CHART.JS
// ─────────────────────────────────────────────────────────────
$volumeLabels = json_encode(array_column($dailyVolume, 'day'));
$volumeData = json_encode(array_map('intval', array_column($dailyVolume, 'cnt')));

$currencyLabels = json_encode(array_column($currencyDist, 'currency'));
$currencyData = json_encode(array_map('intval', array_column($currencyDist, 'cnt')));

// Currency color palette
$currencyColorMap = [
    'INR' => 'rgba(255, 153, 51, 0.85)',
    'UNKNOWN' => 'rgba(156, 163, 175, 0.7)',
];
$currencyColors = [];
foreach (array_column($currencyDist, 'currency') as $c) {
    $currencyColors[] = $currencyColorMap[$c] ?? 'rgba(107, 114, 128, 0.7)';
}
$currencyColorsJson = json_encode($currencyColors);

// Currency symbol map (for dynamic display)
$currencySymbolMap = [
    'INR' => '₹',
    'UNKNOWN' => '',
];

// Step-Up vs Flat SIP doughnut data
$stepUpDoughnutData = json_encode([$stepUpSIP, $flatSIP]);

// Duration Distribution histogram data
$durationLabels = json_encode(array_column($durationDist, 'bucket'));
$durationData = json_encode(array_map('intval', array_column($durationDist, 'cnt')));

// Ambition Index bar chart data
$ambitionLabels = json_encode(array_column($ambitionBuckets, 'goal_bucket'));
$ambitionData = json_encode(array_map('intval', array_column($ambitionBuckets, 'cnt')));

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Admin Insights — SIP SWP Calculator</title>

    <!-- Tailwind CSS CDN (play) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'] },
                    colors: {
                        brand: { 50: '#eff6ff', 100: '#dbeafe', 200: '#bfdbfe', 500: '#3b82f6', 600: '#2563eb', 700: '#1d4ed8', 800: '#1e40af', 900: '#1e3a5f' },
                    }
                }
            }
        }
    </script>

    <!-- Inter font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Chart.js from jsDelivr CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>

    <style>
        body {
            font-family: 'Inter', system-ui, sans-serif;
        }

        .stat-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 24px -4px rgba(0, 0, 0, 0.1);
        }

        .chart-container {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
        }

        .table-row:hover {
            background-color: #f1f5f9;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(12px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-in {
            animation: fadeInUp 0.5s ease forwards;
        }

        .animate-in-delay-1 {
            animation-delay: 0.1s;
        }

        .animate-in-delay-2 {
            animation-delay: 0.2s;
        }

        .animate-in-delay-3 {
            animation-delay: 0.3s;
        }

        .animate-in-delay-4 {
            animation-delay: 0.4s;
        }
    </style>
</head>

<body class="bg-gray-50 text-gray-800 min-h-screen">

    <!-- ── Top Bar ── -->
    <header class="bg-white border-b border-gray-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="/" class="flex items-center space-x-3 group">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                        class="w-10 h-10 rounded-xl shadow-lg shadow-emerald-500/30 transition-transform duration-300 group-hover:scale-105"
                        role="img" aria-label="SIP SWP Calculator Logo">
                        <rect width="24" height="24" rx="6" fill="url(#logo-grad-admin)" />
                        <defs>
                            <linearGradient id="logo-grad-admin" x1="0%" y1="100%" x2="100%" y2="0%">
                                <stop offset="0%" stop-color="#059669" />
                                <stop offset="100%" stop-color="#2dd4bf" />
                            </linearGradient>
                        </defs>
                        <path fill="none" stroke="#ffffff" stroke-width="2.2" stroke-linecap="round"
                            stroke-linejoin="round" d="M4 13l5-5 3.5 3.5 7.5-7.5" />
                        <path fill="none" stroke="#ffffff" stroke-width="2.2" stroke-linecap="round"
                            stroke-linejoin="round" d="M15 4h5v5" />
                        <path fill="none" stroke="#ffffff" stroke-width="2.2" stroke-linecap="round"
                            stroke-linejoin="round" stroke-opacity="0.5" d="M4 17l5-5 3.5 3.5 7.5-7.5" />
                        <path fill="none" stroke="#ffffff" stroke-width="2.2" stroke-linecap="round"
                            stroke-linejoin="round" stroke-opacity="0.25" d="M4 21l5-5 3.5 3.5 7.5-7.5" />
                    </svg>
                </a>
                <div>
                    <h1 class="text-lg font-bold text-gray-900 leading-tight">Admin Insights</h1>
                    <p class="text-xs text-gray-400 leading-tight">sipswpcalculator.com</p>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <span class="hidden sm:inline-flex items-center gap-1.5 text-xs text-gray-400">
                    <span class="w-2 h-2 rounded-full bg-green-400 animate-pulse"></span>
                    Live Data
                </span>
                <a href="?logout=1"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h6a2 2 0 012 2v1" />
                    </svg>
                    Logout
                </a>
            </div>
        </div>
    </header>

    <!-- ── Time Range Filter Tabs ── -->
    <div class="bg-white border-b border-gray-200 sticky top-16 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center overflow-x-auto no-scrollbar gap-1 py-2">
                <?php foreach ($time_ranges as $key => $range):
                    $isActive = ($current_range_key === $key);
                    $btnClass = $isActive
                        ? 'bg-emerald-600 text-white shadow-md shadow-emerald-200'
                        : 'text-gray-500 hover:bg-gray-100 hover:text-gray-700';
                    ?>
                    <a href="?range=<?= $key ?>"
                        class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-semibold transition-all whitespace-nowrap <?= $btnClass ?>">
                        <?= htmlspecialchars($range['label']) ?>
                    </a>
                    <?php
                endforeach; ?>
            </div>
        </div>
    </div>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

        <!-- ── KPI Stat Cards ── -->
        <section>
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-sm font-semibold text-gray-400 uppercase tracking-wider">Overview:
                    <?= htmlspecialchars($current_range['label']) ?>
                </h2>
                <span class="text-xs text-gray-400">Showing data for the selected period</span>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
                <!-- Calculations in Range -->
                <div class="stat-card rounded-xl border border-gray-200 p-5 opacity-0 animate-in">
                    <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">Calculations</p>
                    <p class="mt-2 text-3xl font-extrabold text-gray-900">
                        <?= number_format($totalInRange) ?>
                    </p>
                    <p class="mt-1 text-xs text-gray-400">in this period</p>
                </div>
                <!-- Avg Step-Up -->
                <div class="stat-card rounded-xl border border-gray-200 p-5 opacity-0 animate-in animate-in-delay-1">
                    <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">Avg Step-Up %</p>
                    <p class="mt-2 text-3xl font-extrabold text-emerald-600">
                        <?= number_format($avgStepUp, 1) ?>%
                    </p>
                    <p class="mt-1 text-xs text-gray-400">period average</p>
                </div>
                <!-- Step-Up Adoption -->
                <div class="stat-card rounded-xl border border-gray-200 p-5 opacity-0 animate-in animate-in-delay-2">
                    <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">Step-Up Adoption</p>
                    <p class="mt-2 text-3xl font-extrabold text-emerald-600">
                        <?= number_format($stepUpAdoptionRate, 1) ?>%
                    </p>
                    <p class="mt-1 text-xs text-gray-400">of SIP users</p>
                </div>
                <!-- PDF Downloads -->
                <div class="stat-card rounded-xl border border-gray-200 p-5 opacity-0 animate-in animate-in-delay-3">
                    <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">PDF Downloads</p>
                    <p class="mt-2 text-3xl font-extrabold text-rose-600">
                        <?= number_format($totalPdfDownloads) ?>
                    </p>
                    <p class="mt-1 text-xs text-gray-400">reports generated</p>
                </div>
                <!-- Conversion Rate -->
                <div class="stat-card rounded-xl border border-gray-200 p-5 opacity-0 animate-in animate-in-delay-4">
                    <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">Conversion Rate</p>
                    <p class="mt-2 text-3xl font-extrabold text-violet-600">
                        <?= number_format($conversionRate, 1) ?>%
                    </p>
                    <p class="mt-1 text-xs text-gray-400">calc → PDF</p>
                </div>

                <!-- Secondary Row -->
                <!-- Avg SIP Duration -->
                <div class="stat-card rounded-xl border border-gray-200 p-5 opacity-0 animate-in animate-in-delay-1">
                    <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">Avg SIP Tenure</p>
                    <p class="mt-2 text-3xl font-extrabold text-indigo-600">
                        <?= number_format($avgDurationSIP, 1) ?> <span
                            class="text-xs font-normal text-gray-400">yrs</span>
                    </p>
                    <p class="mt-1.5 text-xs text-gray-500 font-semibold truncate">Avg SIP:
                        <?= $avgSipAmount > 0 ? number_format($avgSipAmount) : 'N/A' ?>
                    </p>
                </div>
                <!-- Avg SWP Duration -->
                <div class="stat-card rounded-xl border border-gray-200 p-5 opacity-0 animate-in animate-in-delay-2">
                    <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">Avg SWP Tenure</p>
                    <p class="mt-2 text-3xl font-extrabold text-indigo-600">
                        <?= number_format($avgDurationSWP, 1) ?> <span
                            class="text-xs font-normal text-gray-400">yrs</span>
                    </p>
                    <p class="mt-1.5 text-xs text-gray-500 font-semibold truncate">Avg SWP:
                        <?= $avgSwpWithdrawal > 0 ? number_format($avgSwpWithdrawal) : 'N/A' ?>
                    </p>
                </div>
                <!-- Avg Interest Rate -->
                <div class="stat-card rounded-xl border border-gray-200 p-5 opacity-0 animate-in animate-in-delay-3">
                    <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">Avg Interest Rate</p>
                    <p class="mt-2 text-3xl font-extrabold text-blue-600">
                        <?= number_format($avgInterestRate, 1) ?>%
                    </p>
                    <p class="mt-1.5 text-xs text-gray-400 truncate">user return rate</p>
                </div>
                <!-- SWP Adoption Rate -->
                <div class="stat-card rounded-xl border border-gray-200 p-5 opacity-0 animate-in animate-in-delay-4">
                    <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">SWP Activation</p>
                    <p class="mt-2 text-3xl font-extrabold text-violet-600">
                        <?= number_format($swpAdoptionRate, 1) ?>%
                    </p>
                    <p class="mt-1.5 text-xs text-gray-400 truncate">combined planning</p>
                </div>
                <!-- All-Time Total -->
                <div class="stat-card rounded-xl border border-gray-200 p-5 opacity-0 animate-in animate-in-delay-5">
                    <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">All-Time Total</p>
                    <p class="mt-2 text-3xl font-extrabold text-gray-900">
                        <?= number_format($totalAllTime) ?>
                    </p>
                    <p class="mt-1.5 text-xs text-gray-400 truncate">calculations total</p>
                </div>
            </div>
        </section>

        <!-- ── Calc Type Breakdown (pills) ── -->
        <?php if (!empty($calcTypeBreakdown)): ?>
            <section>
                <h2 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-4">By Calculator Type</h2>
                <div class="flex flex-wrap gap-3">
                    <?php
                    $pillColors = [
                        'SIP' => 'bg-blue-50 text-blue-700 border-blue-200',
                        'SWP' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                        'DCA' => 'bg-amber-50 text-amber-700 border-amber-200',
                    ];
                    foreach ($calcTypeBreakdown as $row):
                        $color = $pillColors[$row['calc_type']] ?? 'bg-gray-50 text-gray-700 border-gray-200';
                        ?>
                        <div
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-full border <?= $color ?> text-sm font-semibold">
                            <?= htmlspecialchars($row['calc_type']) ?>
                            <span class="bg-white/70 px-2 py-0.5 rounded-full text-xs font-bold">
                                <?= number_format((int) $row['cnt']) ?>
                            </span>
                        </div>
                        <?php
                    endforeach; ?>
                </div>
            </section>
            <?php
        endif; ?>

        <!-- ── Charts ── -->
        <section class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Line Chart: Volume Over Time -->
            <div class="chart-container">
                <h3 class="text-sm font-semibold text-gray-700 mb-4">📈 Calculation Volume <span
                        class="text-gray-400 font-normal">(
                        <?= htmlspecialchars($current_range['label']) ?>)
                    </span></h3>
                <div style="position: relative; height: 280px; width: 100%;">
                    <canvas id="volumeChart"></canvas>
                </div>
            </div>

            <!-- Bar Chart: Currency Popularity -->
            <div class="chart-container">
                <h3 class="text-sm font-semibold text-gray-700 mb-4">💱 Currency Popularity</h3>
                <div style="position: relative; height: 280px; width: 100%;">
                    <canvas id="currencyChart"></canvas>
                </div>
            </div>
        </section>

        <!-- ── Ambition Index ── -->
        <section class="chart-container">
            <h3 class="text-sm font-semibold text-gray-700 mb-4">🎯 Ambition Index <span
                    class="text-gray-400 font-normal">(Investment Goal Distribution)</span></h3>
            <div style="position: relative; height: 300px; width: 100%;">
                <canvas id="ambitionChart"></canvas>
            </div>
        </section>

        <!-- ── Row 2: Doughnut + Histogram ── -->
        <section class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Doughnut: Step-Up vs Flat SIP -->
            <div class="chart-container">
                <h3 class="text-sm font-semibold text-gray-700 mb-4">🍩 Step-Up vs Flat SIP</h3>
                <div style="position: relative; height: 280px; width: 100%;" class="flex items-center justify-center">
                    <canvas id="stepUpDoughnut"></canvas>
                </div>
                <div class="flex justify-center gap-6 mt-4 text-xs text-gray-500">
                    <span class="flex items-center gap-1.5"><span
                            class="w-3 h-3 rounded-full bg-emerald-500 inline-block"></span> Step-Up (
                        <?= (string) $stepUpSIP ?>)
                    </span>
                    <span class="flex items-center gap-1.5"><span
                            class="w-3 h-3 rounded-full bg-gray-300 inline-block"></span> Flat (
                        <?= (string) $flatSIP ?>)
                    </span>
                </div>
            </div>

            <!-- Histogram: Duration Distribution -->
            <div class="chart-container">
                <h3 class="text-sm font-semibold text-gray-700 mb-4">📊 Investment Duration Distribution</h3>
                <div style="position: relative; height: 280px; width: 100%;">
                    <canvas id="durationHistogram"></canvas>
                </div>
            </div>
        </section>

        <!-- ── Corpus Buckets ── -->
        <section class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- INR Corpus Buckets -->
            <div class="chart-container">
                <h3 class="text-sm font-semibold text-gray-700 mb-4">🇮🇳 SWP Corpus Distribution (INR)</h3>
                <?php if (empty($corpusBucketsINR)): ?>
                    <p class="text-sm text-gray-400 italic">No INR SWP data yet.</p>
                    <?php
                else: ?>
                    <div class="space-y-3">
                        <?php
                        $maxBucketINR = max(array_column($corpusBucketsINR, 'cnt'));
                        foreach ($corpusBucketsINR as $b):
                            $bPct = $maxBucketINR > 0 ? round(((int) $b['cnt'] / $maxBucketINR) * 100) : 0;
                            ?>
                            <div>
                                <div class="flex justify-between text-xs mb-1">
                                    <span class="font-medium text-gray-700">
                                        <?= htmlspecialchars($b['bucket']) ?>
                                    </span>
                                    <span class="text-gray-400">
                                        <?= number_format((int) $b['cnt']) ?>
                                    </span>
                                </div>
                                <div class="w-full bg-gray-100 rounded-full h-2.5">
                                    <div class="bg-gradient-to-r from-orange-400 to-orange-500 h-2.5 rounded-full transition-all"
                                        style="width: <?= (string) $bPct ?>%"></div>
                                </div>
                            </div>
                            <?php
                        endforeach; ?>
                    </div>
                    <?php
                endif; ?>
            </div>

            <!-- USD/Other Corpus Buckets -->
            <div class="chart-container">
                <h3 class="text-sm font-semibold text-gray-700 mb-4">🌍 SWP Corpus Distribution (USD & Others)</h3>
                <?php if (empty($corpusBucketsUSD)): ?>
                    <p class="text-sm text-gray-400 italic">No non-INR SWP data yet.</p>
                    <?php
                else: ?>
                    <div class="space-y-3">
                        <?php
                        $maxBucketUSD = max(array_column($corpusBucketsUSD, 'cnt'));
                        foreach ($corpusBucketsUSD as $b):
                            $bPct = $maxBucketUSD > 0 ? round(((int) $b['cnt'] / $maxBucketUSD) * 100) : 0;
                            ?>
                            <div>
                                <div class="flex justify-between text-xs mb-1">
                                    <span class="font-medium text-gray-700">
                                        <?= htmlspecialchars($b['bucket']) ?>
                                    </span>
                                    <span class="text-gray-400">
                                        <?= number_format((int) $b['cnt']) ?>
                                    </span>
                                </div>
                                <div class="w-full bg-gray-100 rounded-full h-2.5">
                                    <div class="bg-gradient-to-r from-blue-400 to-blue-500 h-2.5 rounded-full transition-all"
                                        style="width: <?= (string) $bPct ?>%"></div>
                                </div>
                            </div>
                            <?php
                        endforeach; ?>
                    </div>
                    <?php
                endif; ?>
            </div>
        </section>

        <!-- ── Top 10 SWP Target Corpus ── -->
        <section class="chart-container">
            <h3 class="text-sm font-semibold text-gray-700 mb-4">🏦 Top 10 SWP Target Corpus Amounts</h3>
            <?php if (empty($topCorpus)): ?>
                <p class="text-sm text-gray-400 italic">No SWP calculations recorded yet.</p>
                <?php
            else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200">
                                <th
                                    class="text-left py-3 px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                                    #</th>
                                <th
                                    class="text-left py-3 px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                                    Target Corpus Amount</th>
                                <th
                                    class="text-right py-3 px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                                    Frequency</th>
                                <th
                                    class="text-left py-3 px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider w-1/3">
                                    Distribution</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $maxFreq = !empty($topCorpus) ? (int) $topCorpus[0]['frequency'] : 1;
                            foreach ($topCorpus as $i => $row):
                                $pct = round(((int) $row['frequency'] / $maxFreq) * 100);
                                ?>
                                <tr class="table-row border-b border-gray-100 transition-colors">
                                    <td class="py-3 px-4 text-gray-400 font-mono text-xs">
                                        <?= (string) ($i + 1) ?>
                                    </td>
                                    <td class="py-3 px-4 font-semibold text-gray-800">
                                        <?= $currencySymbolMap[strtoupper($row['currency'] ?? 'INR')] ?? '' ?>
                                        <?= number_format((float) $row['amount']) ?>
                                    </td>
                                    <td class="py-3 px-4 text-right font-mono text-gray-600">
                                        <?= number_format((int) $row['frequency']) ?>
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="w-full bg-gray-100 rounded-full h-2">
                                            <div class="bg-gradient-to-r from-blue-500 to-indigo-500 h-2 rounded-full transition-all"
                                                style="width: <?= (string) $pct ?>%"></div>
                                        </div>
                                    </td>
                                </tr>
                                <?php
                            endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php
            endif; ?>
        </section>

        <!-- ── Top 10 Referrers ── -->
        <section class="chart-container">
            <h3 class="text-sm font-semibold text-gray-700 mb-4">🔗 Top Traffic Sources <span
                    class="text-gray-400 font-normal">(Referrers)</span></h3>
            <?php if (empty($topReferrers)): ?>
                <p class="text-sm text-gray-400 italic">No referrer data recorded yet.</p>
                <?php
            else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200">
                                <th
                                    class="text-left py-3 px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                                    #</th>
                                <th
                                    class="text-left py-3 px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                                    Source</th>
                                <th
                                    class="text-right py-3 px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                                    Visits</th>
                                <th
                                    class="text-left py-3 px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider w-1/3">
                                    Share</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $maxRef = !empty($topReferrers) ? (int) $topReferrers[0]['cnt'] : 1;
                            foreach ($topReferrers as $i => $ref):
                                $refPct = round(((int) $ref['cnt'] / $maxRef) * 100);
                                ?>
                                <tr class="table-row border-b border-gray-100 transition-colors">
                                    <td class="py-3 px-4 text-gray-400 font-mono text-xs">
                                        <?= (string) ($i + 1) ?>
                                    </td>
                                    <td class="py-3 px-4 font-medium text-gray-800 truncate max-w-xs"
                                        title="<?= htmlspecialchars($ref['source']) ?>">
                                        <?= htmlspecialchars($ref['source']) ?>
                                    </td>
                                    <td class="py-3 px-4 text-right font-mono text-gray-600">
                                        <?= number_format((int) $ref['cnt']) ?>
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="w-full bg-gray-100 rounded-full h-2">
                                            <div class="bg-gradient-to-r from-violet-500 to-purple-500 h-2 rounded-full transition-all"
                                                style="width: <?= (string) $refPct ?>%"></div>
                                        </div>
                                    </td>
                                </tr>
                                <?php
                            endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php
            endif; ?>
        </section>

        <!-- ── Footer ── -->
        <footer class="text-center text-xs text-gray-300 pb-8 pt-4">
            Dashboard generated at
            <?= date('d M Y, H:i T') ?> · Data from SQLite (read-only)
        </footer>

    </main>

    <!-- ── Chart.js Initialization ── -->
    <script>
        // Shared defaults
        Chart.defaults.font.family = "'Inter', system-ui, sans-serif";
        Chart.defaults.font.size = 12;
        Chart.defaults.plugins.legend.display = false;
        Chart.defaults.plugins.tooltip.backgroundColor = '#1e293b';
        Chart.defaults.plugins.tooltip.cornerRadius = 8;
        Chart.defaults.plugins.tooltip.padding = 10;

        // ── Line Chart: Volume Over Time ──
        new Chart(document.getElementById('volumeChart'), {
            type: 'line',
            data: {
                labels: <?= $volumeLabels ?>,
                datasets: [{
                    label: 'Calculations',
                    data: <?= $volumeData ?>,
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59,130,246,0.08)',
                    borderWidth: 2.5,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 3,
                    pointHoverRadius: 6,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: '#3b82f6',
                    pointBorderWidth: 2,
                    pointHoverBackgroundColor: '#3b82f6',
                    pointHoverBorderColor: '#fff',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { intersect: false, mode: 'index' },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: '#94a3b8', maxRotation: 45, font: { size: 10 } }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: '#f1f5f9' },
                        ticks: { color: '#94a3b8', precision: 0 }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            title: (items) => {
                                const d = new Date(items[0].label);
                                return d.toLocaleDateString('en-IN', { weekday: 'short', day: 'numeric', month: 'short' });
                            }
                        }
                    }
                }
            }
        });

        // ── Bar Chart: Currency Popularity ──
        new Chart(document.getElementById('currencyChart'), {
            type: 'bar',
            data: {
                labels: <?= $currencyLabels ?>,
                datasets: [{
                    label: 'Count',
                    data: <?= $currencyData ?>,
                    backgroundColor: <?= $currencyColorsJson ?>,
                    borderRadius: 8,
                    borderSkipped: false,
                    maxBarThickness: 56,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: '#64748b', font: { weight: 600 } }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: '#f1f5f9' },
                        ticks: { color: '#94a3b8', precision: 0 }
                    }
                }
            }
        });

        // ── Bar Chart: Ambition Index ──
        new Chart(document.getElementById('ambitionChart'), {
            type: 'bar',
            data: {
                labels: <?= $ambitionLabels ?>,
                datasets: [{
                    label: 'Users',
                    data: <?= $ambitionData ?>,
                    backgroundColor: [
                        'rgba(99, 102, 241, 0.75)',
                        'rgba(59, 130, 246, 0.75)',
                        'rgba(16, 185, 129, 0.75)',
                        'rgba(245, 158, 11, 0.75)',
                        'rgba(239, 68, 68, 0.75)',
                    ],
                    borderRadius: 8,
                    borderSkipped: false,
                    maxBarThickness: 64,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                scales: {
                    x: {
                        beginAtZero: true,
                        grid: { color: '#f1f5f9' },
                        ticks: { color: '#94a3b8', precision: 0 }
                    },
                    y: {
                        grid: { display: false },
                        ticks: { color: '#374151', font: { size: 12, weight: 600 } }
                    }
                },
                plugins: { legend: { display: false } }
            }
        });

        // ── Doughnut Chart: Step-Up vs Flat SIP ──
        new Chart(document.getElementById('stepUpDoughnut'), {
            type: 'doughnut',
            data: {
                labels: ['Step-Up SIP', 'Flat SIP'],
                datasets: [{
                    data: <?= $stepUpDoughnutData ?>,
                    backgroundColor: ['rgba(16, 185, 129, 0.85)', 'rgba(209, 213, 219, 0.7)'],
                    borderWidth: 0,
                    hoverOffset: 8,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '65%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: (ctx) => {
                                const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                                const pct = total > 0 ? ((ctx.parsed / total) * 100).toFixed(1) : 0;
                                return `${ctx.label}: ${ctx.parsed} (${pct}%)`;
                            }
                        }
                    }
                }
            }
        });

        // ── Bar Chart: Duration Distribution (Histogram) ──
        new Chart(document.getElementById('durationHistogram'), {
            type: 'bar',
            data: {
                labels: <?= $durationLabels ?>,
                datasets: [{
                    label: 'Calculations',
                    data: <?= $durationData ?>,
                    backgroundColor: 'rgba(139, 92, 246, 0.7)',
                    borderRadius: 6,
                    borderSkipped: false,
                    maxBarThickness: 48,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: '#64748b', font: { size: 11, weight: 500 } }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: '#f1f5f9' },
                        ticks: { color: '#94a3b8', precision: 0 }
                    }
                },
                plugins: { legend: { display: false } }
            }
        });
    </script>

</body>

</html>

<?php
// ─────────────────────────────────────────────────────────────
// LOGIN PAGE FUNCTION
// ─────────────────────────────────────────────────────────────
function showLoginPage(string $error = ''): void
{
    ?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="robots" content="noindex, nofollow">
        <title>Admin Login — SIP SWP Calculator</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
        <style>
            body {
                font-family: 'Inter', system-ui, sans-serif;
            }
        </style>
    </head>

    <body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">
        <div class="w-full max-w-sm">
            <div class="bg-white rounded-2xl shadow-xl border border-gray-100 p-8">
                <div class="flex justify-center mb-6">
                    <div class="group">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                            class="w-14 h-14 rounded-2xl shadow-lg shadow-emerald-500/30 transition-transform duration-300 group-hover:scale-105"
                            role="img" aria-label="SIP SWP Calculator Logo">
                            <rect width="24" height="24" rx="6" fill="url(#logo-grad-admin-login)" />
                            <defs>
                                <linearGradient id="logo-grad-admin-login" x1="0%" y1="100%" x2="100%" y2="0%">
                                    <stop offset="0%" stop-color="#059669" />
                                    <stop offset="100%" stop-color="#2dd4bf" />
                                </linearGradient>
                            </defs>
                            <path fill="none" stroke="#ffffff" stroke-width="2.2" stroke-linecap="round"
                                stroke-linejoin="round" d="M4 13l5-5 3.5 3.5 7.5-7.5" />
                            <path fill="none" stroke="#ffffff" stroke-width="2.2" stroke-linecap="round"
                                stroke-linejoin="round" d="M15 4h5v5" />
                            <path fill="none" stroke="#ffffff" stroke-width="2.2" stroke-linecap="round"
                                stroke-linejoin="round" stroke-opacity="0.5" d="M4 17l5-5 3.5 3.5 7.5-7.5" />
                            <path fill="none" stroke="#ffffff" stroke-width="2.2" stroke-linecap="round"
                                stroke-linejoin="round" stroke-opacity="0.25" d="M4 21l5-5 3.5 3.5 7.5-7.5" />
                        </svg>
                    </div>
                </div>
                <h1 class="text-xl font-bold text-gray-900 text-center">Admin Insights</h1>
                <p class="text-sm text-gray-400 text-center mt-1 mb-6">Enter your password to continue</p>

                <?php if ($error): ?>
                    <div
                        class="mb-4 p-3 bg-red-50 border border-red-200 text-red-600 text-sm rounded-lg flex items-center gap-2">
                        <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                clip-rule="evenodd" />
                        </svg>
                        <?= htmlspecialchars($error) ?>
                    </div>
                    <?php
                endif; ?>

                <form method="POST" action="">
                    <label for="password" class="block text-sm font-medium text-gray-600 mb-1.5">Password</label>
                    <input type="password" id="password" name="password" required autofocus placeholder="••••••••••"
                        class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-shadow">
                    <button type="submit"
                        class="mt-4 w-full py-2.5 px-4 bg-gradient-to-r from-emerald-600 to-teal-600 text-white text-sm font-semibold rounded-xl hover:from-emerald-700 hover:to-teal-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition-all shadow-lg shadow-emerald-500/25">
                        Unlock Dashboard
                    </button>
                </form>
            </div>
            <p class="text-center text-xs text-gray-300 mt-6">sipswpcalculator.com · Private Admin</p>
        </div>
    </body>

    </html>
    <?php
}
?>