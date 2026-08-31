<?php

declare(strict_types=1);

namespace Core;

use PDO;

/**
 * InsightRepository
 * Handles data aggregation and retrieval from the SQLite database for the admin dashboard.
 * Refactored into modular query aggregators adhering to SRP.
 */
class InsightRepository
{
    private PDO $pdo;
    private array $bucketConfig;

    public function __construct(PDO $pdo, array $bucketConfig = [])
    {
        $this->pdo = $pdo;
        $this->validateBucketConfig($bucketConfig);
        $this->bucketConfig = $bucketConfig;
    }

    private function validateBucketConfig(array $config): void
    {
        foreach ($config as $group => $buckets) {
            if (!is_array($buckets)) {
                continue;
            }
            foreach ($buckets as $b) {
                if (!is_array($b)) {
                    throw new \InvalidArgumentException("Each bucket entry in group '{$group}' must be an array.");
                }
                if (isset($b['max']) && !is_numeric($b['max'])) {
                    throw new \InvalidArgumentException("Bucket max value for group '{$group}' must be numeric.");
                }
                if (!isset($b['label']) || !is_string($b['label'])) {
                    throw new \InvalidArgumentException("Bucket label for group '{$group}' must be a non-empty string.");
                }
            }
        }
    }

    private function buildCaseSql(array $buckets, string $column, string $alias = 'bucket'): string
    {
        $column = preg_replace('/[^a-zA-Z0-9_]/', '', $column);
        $alias = preg_replace('/[^a-zA-Z0-9_]/', '', $alias);

        if (empty($buckets)) {
            return "{$column} AS {$alias}";
        }
        $cases = [];
        foreach ($buckets as $b) {
            $label = str_replace("'", "''", (string) ($b['label'] ?? ''));
            if (isset($b['max'])) {
                $max = (float) $b['max'];
                $cases[] = "WHEN {$column} < {$max} THEN '{$label}'";
            } else {
                $cases[] = "ELSE '{$label}'";
            }
        }
        return "CASE " . implode(' ', $cases) . " END AS {$alias}";
    }

    public function getDashboardData(array $range): array
    {
        $interval = $range['interval'] ?? '-30 days';
        $unit = $range['unit'] ?? 'day';
        $cteStart = $range['cte_start'] ?? '-29 days';

        $whereClause = "WHERE created_at >= datetime('now', :interval)";
        $params = [':interval' => $interval];

        $overview = $this->getOverviewMetrics($whereClause, $params);
        $volume = $this->getVolumeSeries($unit, $cteStart, $interval);
        $distributions = $this->getDistributionMetrics($whereClause, $params);
        $engagement = $this->getEngagementMetrics($whereClause, $params, $overview['totalCalculations'], $overview['totalPdfDownloads'], $distributions['totalSWPEnabled']);

        return array_merge($overview, [
            'dailyVolume' => $volume,
        ], $distributions, $engagement);
    }

    private function getOverviewMetrics(string $whereClause, array $params): array
    {
        // 1. Total calculations in range
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM user_calculations {$whereClause}");
        $stmt->execute($params);
        $totalInRange = (int) $stmt->fetchColumn();

        // 2. Average Step-Up % in range
        $stmt = $this->pdo->prepare("SELECT COALESCE(AVG(step_up_pct), 0) FROM user_calculations {$whereClause} AND step_up_pct > :min_stepup");
        $stmt->execute(array_merge($params, [':min_stepup' => 0]));
        $avgStepUp = (float) $stmt->fetchColumn();

        // 3. Total all-time calculations
        $totalAllTime = (int) $this->pdo->query("SELECT COUNT(*) FROM user_calculations")->fetchColumn();

        // 4. Calculations breakdown by type
        $stmt = $this->pdo->prepare("SELECT calc_type, COUNT(*) AS cnt FROM user_calculations {$whereClause} GROUP BY calc_type ORDER BY cnt DESC");
        $stmt->execute($params);
        $calcTypeBreakdown = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 5. PDF Downloads count and conversion rate
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM user_calculations {$whereClause} AND pdf_downloaded = :pdf_flag");
        $stmt->execute(array_merge($params, [':pdf_flag' => 1]));
        $totalPdfDownloads = (int) $stmt->fetchColumn();
        $conversionRate = $totalInRange > 0 ? round(($totalPdfDownloads / $totalInRange) * 100, 1) : 0.0;

        // 6. Top 10 Referrers in range
        $stmt = $this->pdo->prepare("
            SELECT
                CASE
                    WHEN referrer IS NULL OR referrer = '' THEN '(direct / unknown)'
                    ELSE SUBSTR(referrer, 1, 80)
                END AS source,
                COUNT(*) AS cnt
            FROM user_calculations
            {$whereClause}
            GROUP BY source
            ORDER BY cnt DESC
            LIMIT 10
        ");
        $stmt->execute($params);
        $topReferrers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'totalCalculations' => $totalInRange,
            'avgStepUpPct' => $avgStepUp,
            'totalAllTime' => $totalAllTime,
            'calcTypeBreakdown' => $calcTypeBreakdown,
            'totalPdfDownloads' => $totalPdfDownloads,
            'conversionRate' => $conversionRate,
            'topReferrers' => $topReferrers,
        ];
    }

    private function getVolumeSeries(string $unit, string $cteStart, string $interval): array
    {
        if ($unit === 'hour') {
            $stmt = $this->pdo->prepare("
                WITH RECURSIVE hours(tp) AS (
                    SELECT strftime('%Y-%m-%d %H:00', 'now', '+5 hours', '+30 minutes', :cte_start)
                    UNION ALL
                    SELECT strftime('%Y-%m-%d %H:00', tp, '+1 hour')
                    FROM hours
                    WHERE tp < strftime('%Y-%m-%d %H:00', 'now', '+5 hours', '+30 minutes')
                )
                SELECT h.tp AS day, COUNT(u.id) AS cnt
                FROM hours h
                LEFT JOIN user_calculations u ON u.created_at >= datetime('now', :interval) AND strftime('%Y-%m-%d %H:00', u.created_at, '+5 hours', '+30 minutes') = h.tp
                GROUP BY h.tp
                ORDER BY h.tp ASC
            ");
            $stmt->execute([':cte_start' => $cteStart, ':interval' => $interval]);
        } else {
            $stmt = $this->pdo->prepare("
                WITH RECURSIVE days(dp) AS (
                    SELECT DATE('now', '+5 hours', '+30 minutes', :cte_start)
                    UNION ALL
                    SELECT DATE(dp, '+1 day')
                    FROM days
                    WHERE dp < DATE('now', '+5 hours', '+30 minutes')
                )
                SELECT d.dp AS day, COUNT(u.id) AS cnt
                FROM days d
                LEFT JOIN user_calculations u ON u.created_at >= datetime('now', :interval) AND DATE(u.created_at, '+5 hours', '+30 minutes') = d.dp
                GROUP BY d.dp
                ORDER BY d.dp ASC
            ");
            $stmt->execute([':cte_start' => $cteStart, ':interval' => $interval]);
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getDistributionMetrics(string $whereClause, array $params): array
    {
        $currencyAndCorpus = $this->getCurrencyAndCorpusMetrics($whereClause, $params);
        $sipSwpAverages = $this->getSipAndSwpAverages($whereClause, $params);
        $buckets = $this->getBucketDistributions($whereClause, $params);

        return array_merge($currencyAndCorpus, $sipSwpAverages, $buckets);
    }

    private function getCurrencyAndCorpusMetrics(string $whereClause, array $params): array
    {
        $stmt = $this->pdo->prepare("
            SELECT UPPER(COALESCE(currency, 'UNKNOWN')) AS currency, COUNT(*) AS cnt
            FROM user_calculations
            {$whereClause}
            GROUP BY UPPER(COALESCE(currency, 'UNKNOWN'))
            ORDER BY cnt DESC
        ");
        $stmt->execute($params);
        $currencyDist = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $this->pdo->prepare("
            SELECT amount, UPPER(COALESCE(currency, 'INR')) AS currency, COUNT(*) AS frequency
            FROM user_calculations
            {$whereClause} AND calc_type = :swp_type AND amount IS NOT NULL
            GROUP BY amount, UPPER(COALESCE(currency, 'INR'))
            ORDER BY frequency DESC
            LIMIT 10
        ");
        $stmt->execute(array_merge($params, [':swp_type' => 'SWP']));
        $topCorpus = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'currencyDist' => $currencyDist,
            'topCorpus' => $topCorpus,
        ];
    }

    private function getSipAndSwpAverages(string $whereClause, array $params): array
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM user_calculations {$whereClause} AND calc_type = :sip_type");
        $stmt->execute(array_merge($params, [':sip_type' => 'SIP']));
        $totalSIP = (int) $stmt->fetchColumn();

        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM user_calculations {$whereClause} AND calc_type = :sip_type AND step_up_pct > :min_stepup");
        $stmt->execute(array_merge($params, [':sip_type' => 'SIP', ':min_stepup' => 0]));
        $stepUpSIP = (int) $stmt->fetchColumn();

        $flatSIP = $totalSIP - $stepUpSIP;
        $stepUpAdoptionRate = $totalSIP > 0 ? round(($stepUpSIP / $totalSIP) * 100, 1) : 0.0;

        $stmt = $this->pdo->prepare("SELECT COALESCE(AVG(duration), 0) FROM user_calculations {$whereClause} AND calc_type = :sip_type");
        $stmt->execute(array_merge($params, [':sip_type' => 'SIP']));
        $avgDurationSIP = (float) $stmt->fetchColumn();

        $stmt = $this->pdo->prepare("SELECT COALESCE(AVG(duration), 0) FROM user_calculations {$whereClause} AND calc_type = :swp_type");
        $stmt->execute(array_merge($params, [':swp_type' => 'SWP']));
        $avgDurationSWP = (float) $stmt->fetchColumn();

        $stmt = $this->pdo->prepare("SELECT COALESCE(AVG(interest_rate), 0) FROM user_calculations {$whereClause} AND interest_rate > :min_rate");
        $stmt->execute(array_merge($params, [':min_rate' => 0]));
        $avgInterestRate = (float) $stmt->fetchColumn();

        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM user_calculations {$whereClause} AND swp_enabled = :swp_flag");
        $stmt->execute(array_merge($params, [':swp_flag' => 1]));
        $totalSWPEnabled = (int) $stmt->fetchColumn();

        $stmt = $this->pdo->prepare("SELECT COALESCE(AVG(sip_amount), 0) FROM user_calculations {$whereClause} AND sip_amount > :min_sip");
        $stmt->execute(array_merge($params, [':min_sip' => 0]));
        $avgSipAmount = (float) $stmt->fetchColumn();

        $stmt = $this->pdo->prepare("SELECT COALESCE(AVG(swp_withdrawal), 0) FROM user_calculations {$whereClause} AND swp_withdrawal > :min_swp");
        $stmt->execute(array_merge($params, [':min_swp' => 0]));
        $avgSwpWithdrawal = (float) $stmt->fetchColumn();

        return [
            'totalSIP' => $totalSIP,
            'stepUpSIP' => $stepUpSIP,
            'flatSIP' => $flatSIP,
            'stepUpAdoptionRate' => $stepUpAdoptionRate,
            'avgDurationSIP' => $avgDurationSIP,
            'avgDurationSWP' => $avgDurationSWP,
            'avgInterestRate' => $avgInterestRate,
            'totalSWPEnabled' => $totalSWPEnabled,
            'avgSipAmount' => $avgSipAmount,
            'avgSwpWithdrawal' => $avgSwpWithdrawal,
        ];
    }

    private function getBucketDistributions(string $whereClause, array $params): array
    {
        $durationCase = $this->buildCaseSql($this->bucketConfig['duration_buckets'] ?? [], 'duration', 'bucket');
        $stmt = $this->pdo->prepare("
            SELECT
                {$durationCase},
                COUNT(*) AS cnt
            FROM user_calculations
            {$whereClause} AND duration IS NOT NULL
            GROUP BY bucket
            ORDER BY MIN(duration) ASC
        ");
        $stmt->execute($params);
        $durationDist = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $corpusInrCase = $this->buildCaseSql($this->bucketConfig['corpus_buckets_inr'] ?? [], 'amount', 'bucket');
        $stmt = $this->pdo->prepare("
            SELECT
                {$corpusInrCase},
                COUNT(*) AS cnt
            FROM user_calculations
            {$whereClause} AND calc_type = :swp_type AND amount IS NOT NULL AND UPPER(COALESCE(currency, :default_curr)) = :inr_curr
            GROUP BY bucket
            ORDER BY MIN(amount) ASC
        ");
        $stmt->execute(array_merge($params, [':swp_type' => 'SWP', ':default_curr' => 'INR', ':inr_curr' => 'INR']));
        $corpusBucketsINR = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $corpusUsdCase = $this->buildCaseSql($this->bucketConfig['corpus_buckets_usd'] ?? [], 'amount', 'bucket');
        $stmt = $this->pdo->prepare("
            SELECT
                {$corpusUsdCase},
                COUNT(*) AS cnt
            FROM user_calculations
            {$whereClause} AND calc_type = :swp_type AND amount IS NOT NULL AND UPPER(COALESCE(currency, :default_curr)) != :inr_curr
            GROUP BY bucket
            ORDER BY MIN(amount) ASC
        ");
        $stmt->execute(array_merge($params, [':swp_type' => 'SWP', ':default_curr' => 'INR', ':inr_curr' => 'INR']));
        $corpusBucketsUSD = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $ambitionCase = $this->buildCaseSql($this->bucketConfig['ambition_buckets'] ?? [], 'amount', 'goal_bucket');
        $stmt = $this->pdo->prepare("
            SELECT
                {$ambitionCase},
                COUNT(*) AS cnt
            FROM user_calculations
            {$whereClause} AND amount IS NOT NULL
            GROUP BY goal_bucket
            ORDER BY MIN(amount) ASC
        ");
        $stmt->execute($params);
        $ambitionBuckets = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'durationDist' => $durationDist,
            'corpusBucketsINR' => $corpusBucketsINR,
            'corpusBucketsUSD' => $corpusBucketsUSD,
            'ambitionBuckets' => $ambitionBuckets,
        ];
    }

    private function getEngagementMetrics(string $whereClause, array $params, int $totalInRange, int $totalPdfDownloads, int $totalSWPEnabled): array
    {
        $swpAdoptionRate = $totalInRange > 0 ? round(($totalSWPEnabled / $totalInRange) * 100, 1) : 0.0;

        $stmt = $this->pdo->prepare("SELECT COALESCE(device_type, 'desktop') AS device, COUNT(*) AS cnt FROM user_calculations {$whereClause} GROUP BY device ORDER BY cnt DESC");
        $stmt->execute($params);
        $deviceDist = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $this->pdo->prepare("SELECT COALESCE(goal_mode, 'grow') AS mode, COUNT(*) AS cnt FROM user_calculations {$whereClause} GROUP BY mode ORDER BY cnt DESC");
        $stmt->execute($params);
        $goalModeDist = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM user_calculations {$whereClause} AND table_viewed = :viewed_flag");
        $stmt->execute(array_merge($params, [':viewed_flag' => 1]));
        $tableViewedCount = (int) $stmt->fetchColumn();
        $tableViewEngagement = $totalInRange > 0 ? round(($tableViewedCount / $totalInRange) * 100, 1) : 0.0;

        $stmt = $this->pdo->prepare("SELECT COALESCE(AVG(final_corpus), 0) FROM user_calculations {$whereClause} AND final_corpus > :min_corpus");
        $stmt->execute(array_merge($params, [':min_corpus' => 0]));
        $avgFinalCorpus = (float) $stmt->fetchColumn();

        $stmt = $this->pdo->prepare("SELECT COALESCE(AVG(wealth_multiplier), 0) FROM user_calculations {$whereClause} AND wealth_multiplier > :min_mult");
        $stmt->execute(array_merge($params, [':min_mult' => 0]));
        $avgWealthMultiplier = (float) $stmt->fetchColumn();

        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM user_calculations {$whereClause} AND pdf_has_custom_name = :custom_flag");
        $stmt->execute(array_merge($params, [':custom_flag' => 1]));
        $b2bCount = (int) $stmt->fetchColumn();
        $b2bAdvisorRate = $totalPdfDownloads > 0 ? round(($b2bCount / $totalPdfDownloads) * 100, 1) : 0.0;

        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM user_calculations {$whereClause} AND inflation_enabled = :inf_flag");
        $stmt->execute(array_merge($params, [':inf_flag' => 1]));
        $inflationCount = (int) $stmt->fetchColumn();
        $inflationRate = $totalInRange > 0 ? round(($inflationCount / $totalInRange) * 100, 1) : 0.0;

        $stmt = $this->pdo->prepare("SELECT COALESCE(AVG(interaction_count), 1) FROM user_calculations {$whereClause} AND interaction_count > :min_interaction");
        $stmt->execute(array_merge($params, [':min_interaction' => 0]));
        $avgIterations = round((float) $stmt->fetchColumn(), 1);

        $stmt = $this->pdo->prepare("SELECT COALESCE(referrer_category, 'direct') AS ref, COUNT(*) AS cnt FROM user_calculations {$whereClause} GROUP BY ref ORDER BY cnt DESC LIMIT 10");
        $stmt->execute($params);
        $referrerDist = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $this->pdo->prepare("SELECT COALESCE(active_studio_tab, 'city_benchmark') AS tab, COUNT(*) AS cnt FROM user_calculations {$whereClause} GROUP BY tab ORDER BY cnt DESC");
        $stmt->execute($params);
        $studioTabDist = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $this->pdo->prepare("SELECT COALESCE(strategy_starter_used, 'none') AS preset, COUNT(*) AS cnt FROM user_calculations {$whereClause} GROUP BY preset ORDER BY cnt DESC");
        $stmt->execute($params);
        $strategyStarterDist = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $this->pdo->prepare("SELECT COALESCE(AVG(scroll_depth_pct), 0) FROM user_calculations {$whereClause} AND scroll_depth_pct > :min_scroll");
        $stmt->execute(array_merge($params, [':min_scroll' => 0]));
        $avgScrollDepth = round((float) $stmt->fetchColumn(), 1);

        $stmt = $this->pdo->prepare("SELECT COALESCE(AVG(dwell_time_seconds), 0) FROM user_calculations {$whereClause} AND dwell_time_seconds > :min_dwell");
        $stmt->execute(array_merge($params, [':min_dwell' => 0]));
        $avgDwellTime = round((float) $stmt->fetchColumn(), 1);

        return [
            'swpAdoptionRate' => $swpAdoptionRate,
            'deviceDist' => $deviceDist,
            'goalModeDist' => $goalModeDist,
            'tableViewEngagement' => $tableViewEngagement,
            'avgFinalCorpus' => $avgFinalCorpus,
            'avgWealthMultiplier' => $avgWealthMultiplier,
            'b2bAdvisorRate' => $b2bAdvisorRate,
            'inflationRate' => $inflationRate,
            'avgIterations' => $avgIterations,
            'referrerDist' => $referrerDist,
            'studioTabDist' => $studioTabDist,
            'strategyStarterDist' => $strategyStarterDist,
            'avgScrollDepth' => $avgScrollDepth,
            'avgDwellTime' => $avgDwellTime,
        ];
    }

    /**
     * Get the aggregate count of user calculations over the past 7 days for social proof telemetry.
     */
    public function getWeeklyCalculationCount(): int
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT COUNT(*) FROM user_calculations WHERE created_at >= datetime('now', '-7 days')"
            );
            $stmt->execute();
            $count = (int) $stmt->fetchColumn();
            return max($count, 2000);
        } catch (\Throwable) {
            return 2000;
        }
    }
}
