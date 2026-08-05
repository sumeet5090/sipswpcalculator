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

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
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
        $engagement = $this->getEngagementMetrics($whereClause, $params, $overview['totalInRange'], $overview['totalPdfDownloads']);

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
        $stmt = $this->pdo->prepare("SELECT COALESCE(AVG(step_up_pct), 0) FROM user_calculations {$whereClause} AND step_up_pct > 0");
        $stmt->execute($params);
        $avgStepUp = (float) $stmt->fetchColumn();

        // 3. Total all-time calculations
        $totalAllTime = (int) $this->pdo->query("SELECT COUNT(*) FROM user_calculations")->fetchColumn();

        // 4. Calculations breakdown by type
        $stmt = $this->pdo->prepare("SELECT calc_type, COUNT(*) AS cnt FROM user_calculations {$whereClause} GROUP BY calc_type ORDER BY cnt DESC");
        $stmt->execute($params);
        $calcTypeBreakdown = $stmt->fetchAll();

        // 5. PDF Downloads count and conversion rate
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM user_calculations {$whereClause} AND pdf_downloaded = 1");
        $stmt->execute($params);
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
        $topReferrers = $stmt->fetchAll();

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
                    SELECT strftime('%Y-%m-%d %H:00', 'now', :cte_start)
                    UNION ALL
                    SELECT strftime('%Y-%m-%d %H:00', tp, '+1 hour')
                    FROM hours
                    WHERE tp < strftime('%Y-%m-%d %H:00', 'now')
                )
                SELECT h.tp AS day, COUNT(u.id) AS cnt
                FROM hours h
                LEFT JOIN user_calculations u ON u.created_at >= datetime('now', :interval) AND strftime('%Y-%m-%d %H:00', u.created_at) = h.tp
                GROUP BY h.tp
                ORDER BY h.tp ASC
            ");
            $stmt->execute([':cte_start' => $cteStart, ':interval' => $interval]);
        } else {
            $stmt = $this->pdo->prepare("
                WITH RECURSIVE days(dp) AS (
                    SELECT DATE('now', :cte_start)
                    UNION ALL
                    SELECT DATE(dp, '+1 day')
                    FROM days
                    WHERE dp < DATE('now')
                )
                SELECT d.dp AS day, COUNT(u.id) AS cnt
                FROM days d
                LEFT JOIN user_calculations u ON u.created_at >= datetime('now', :interval) AND DATE(u.created_at) = d.dp
                GROUP BY d.dp
                ORDER BY d.dp ASC
            ");
            $stmt->execute([':cte_start' => $cteStart, ':interval' => $interval]);
        }

        return $stmt->fetchAll();
    }

    private function getDistributionMetrics(string $whereClause, array $params): array
    {
        // Currency distribution
        $stmt = $this->pdo->prepare("
            SELECT UPPER(COALESCE(currency, 'UNKNOWN')) AS currency, COUNT(*) AS cnt
            FROM user_calculations
            {$whereClause}
            GROUP BY UPPER(COALESCE(currency, 'UNKNOWN'))
            ORDER BY cnt DESC
        ");
        $stmt->execute($params);
        $currencyDist = $stmt->fetchAll();

        // Top 10 SWP target corpus amounts
        $stmt = $this->pdo->prepare("
            SELECT amount, UPPER(COALESCE(currency, 'INR')) AS currency, COUNT(*) AS frequency
            FROM user_calculations
            {$whereClause} AND calc_type = 'SWP' AND amount IS NOT NULL
            GROUP BY amount, UPPER(COALESCE(currency, 'INR'))
            ORDER BY frequency DESC
            LIMIT 10
        ");
        $stmt->execute($params);
        $topCorpus = $stmt->fetchAll();

        // SIP Step-Up metrics
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM user_calculations {$whereClause} AND calc_type = 'SIP'");
        $stmt->execute($params);
        $totalSIP = (int) $stmt->fetchColumn();

        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM user_calculations {$whereClause} AND calc_type = 'SIP' AND step_up_pct > 0");
        $stmt->execute($params);
        $stepUpSIP = (int) $stmt->fetchColumn();

        $flatSIP = $totalSIP - $stepUpSIP;
        $stepUpAdoptionRate = $totalSIP > 0 ? round(($stepUpSIP / $totalSIP) * 100, 1) : 0.0;

        // Average Durations & Rates
        $stmt = $this->pdo->prepare("SELECT COALESCE(AVG(duration), 0) FROM user_calculations {$whereClause} AND calc_type = 'SIP'");
        $stmt->execute($params);
        $avgDurationSIP = (float) $stmt->fetchColumn();

        $stmt = $this->pdo->prepare("SELECT COALESCE(AVG(duration), 0) FROM user_calculations {$whereClause} AND calc_type = 'SWP'");
        $stmt->execute($params);
        $avgDurationSWP = (float) $stmt->fetchColumn();

        $stmt = $this->pdo->prepare("SELECT COALESCE(AVG(interest_rate), 0) FROM user_calculations {$whereClause} AND interest_rate > 0");
        $stmt->execute($params);
        $avgInterestRate = (float) $stmt->fetchColumn();

        // SWP Adoption
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM user_calculations {$whereClause} AND swp_enabled = 1");
        $stmt->execute($params);
        $totalSWPEnabled = (int) $stmt->fetchColumn();

        $stmt = $this->pdo->prepare("SELECT COALESCE(AVG(sip_amount), 0) FROM user_calculations {$whereClause} AND sip_amount > 0");
        $stmt->execute($params);
        $avgSipAmount = (float) $stmt->fetchColumn();

        $stmt = $this->pdo->prepare("SELECT COALESCE(AVG(swp_withdrawal), 0) FROM user_calculations {$whereClause} AND swp_withdrawal > 0");
        $stmt->execute($params);
        $avgSwpWithdrawal = (float) $stmt->fetchColumn();

        // Duration distribution buckets
        $stmt = $this->pdo->prepare("
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
            {$whereClause} AND duration IS NOT NULL
            GROUP BY bucket
            ORDER BY MIN(duration) ASC
        ");
        $stmt->execute($params);
        $durationDist = $stmt->fetchAll();

        // Corpus Buckets (INR)
        $stmt = $this->pdo->prepare("
            SELECT
                CASE
                    WHEN amount < 1000000 THEN 'Under 10L'
                    WHEN amount < 5000000 THEN '10L – 50L'
                    WHEN amount < 10000000 THEN '50L – 1Cr'
                    ELSE 'Above 1Cr'
                END AS bucket,
                COUNT(*) AS cnt
            FROM user_calculations
            {$whereClause} AND calc_type = 'SWP' AND amount IS NOT NULL AND UPPER(COALESCE(currency,'INR')) = 'INR'
            GROUP BY bucket
            ORDER BY MIN(amount) ASC
        ");
        $stmt->execute($params);
        $corpusBucketsINR = $stmt->fetchAll();

        // Corpus Buckets (USD/Others)
        $stmt = $this->pdo->prepare("
            SELECT
                CASE
                    WHEN amount < 10000 THEN 'Under 10K'
                    WHEN amount < 50000 THEN '10K – 50K'
                    WHEN amount < 100000 THEN '50K – 100K'
                    ELSE 'Above 100K'
                END AS bucket,
                COUNT(*) AS cnt
            FROM user_calculations
            {$whereClause} AND calc_type = 'SWP' AND amount IS NOT NULL AND UPPER(COALESCE(currency,'INR')) != 'INR'
            GROUP BY bucket
            ORDER BY MIN(amount) ASC
        ");
        $stmt->execute($params);
        $corpusBucketsUSD = $stmt->fetchAll();

        // Ambition Index buckets
        $stmt = $this->pdo->prepare("
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
            {$whereClause} AND amount IS NOT NULL
            GROUP BY goal_bucket
            ORDER BY MIN(amount) ASC
        ");
        $stmt->execute($params);
        $ambitionBuckets = $stmt->fetchAll();

        return [
            'currencyDist' => $currencyDist,
            'topCorpus' => $topCorpus,
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
            'durationDist' => $durationDist,
            'corpusBucketsINR' => $corpusBucketsINR,
            'corpusBucketsUSD' => $corpusBucketsUSD,
            'ambitionBuckets' => $ambitionBuckets,
        ];
    }

    private function getEngagementMetrics(string $whereClause, array $params, int $totalInRange, int $totalPdfDownloads): array
    {
        $swpAdoptionRate = $totalInRange > 0 ? round(($params[':interval'] ? 0 : 0) /* recalculate if needed */, 1) : 0.0;

        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM user_calculations {$whereClause} AND swp_enabled = 1");
        $stmt->execute($params);
        $totalSWPEnabled = (int) $stmt->fetchColumn();
        $swpAdoptionRate = $totalInRange > 0 ? round(($totalSWPEnabled / $totalInRange) * 100, 1) : 0.0;

        $stmt = $this->pdo->prepare("SELECT COALESCE(device_type, 'desktop') AS device, COUNT(*) AS cnt FROM user_calculations {$whereClause} GROUP BY device ORDER BY cnt DESC");
        $stmt->execute($params);
        $deviceDist = $stmt->fetchAll();

        $stmt = $this->pdo->prepare("SELECT COALESCE(goal_mode, 'grow') AS mode, COUNT(*) AS cnt FROM user_calculations {$whereClause} GROUP BY mode ORDER BY cnt DESC");
        $stmt->execute($params);
        $goalModeDist = $stmt->fetchAll();

        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM user_calculations {$whereClause} AND table_viewed = 1");
        $stmt->execute($params);
        $tableViewedCount = (int) $stmt->fetchColumn();
        $tableViewEngagement = $totalInRange > 0 ? round(($tableViewedCount / $totalInRange) * 100, 1) : 0.0;

        $stmt = $this->pdo->prepare("SELECT COALESCE(AVG(final_corpus), 0) FROM user_calculations {$whereClause} AND final_corpus > 0");
        $stmt->execute($params);
        $avgFinalCorpus = (float) $stmt->fetchColumn();

        $stmt = $this->pdo->prepare("SELECT COALESCE(AVG(wealth_multiplier), 0) FROM user_calculations {$whereClause} AND wealth_multiplier > 0");
        $stmt->execute($params);
        $avgWealthMultiplier = (float) $stmt->fetchColumn();

        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM user_calculations {$whereClause} AND pdf_has_custom_name = 1");
        $stmt->execute($params);
        $b2bCount = (int) $stmt->fetchColumn();
        $b2bAdvisorRate = $totalPdfDownloads > 0 ? round(($b2bCount / $totalPdfDownloads) * 100, 1) : 0.0;

        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM user_calculations {$whereClause} AND inflation_enabled = 1");
        $stmt->execute($params);
        $inflationCount = (int) $stmt->fetchColumn();
        $inflationRate = $totalInRange > 0 ? round(($inflationCount / $totalInRange) * 100, 1) : 0.0;

        $stmt = $this->pdo->prepare("SELECT COALESCE(AVG(interaction_count), 1) FROM user_calculations {$whereClause} AND interaction_count > 0");
        $stmt->execute($params);
        $avgIterations = round((float) $stmt->fetchColumn(), 1);

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
        ];
    }
}
