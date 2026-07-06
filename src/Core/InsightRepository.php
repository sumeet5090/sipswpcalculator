<?php

declare(strict_types=1);

namespace Core;

use PDO;

/**
 * InsightRepository
 * Handles data aggregation and retrieval from the SQLite database for the admin dashboard.
 */
class InsightRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? DatabaseManager::getConnection();
    }

    /**
     * Get all KPI aggregates and datasets for a specified time range interval.
     *
     * @param string $interval SQLite interval string (e.g. '-1 day', '-7 days', etc.)
     * @return array
     */
    public function getDashboardData(string $interval): array
    {
        $where_clause = "WHERE created_at >= datetime('now', :interval)";
        $params = [':interval' => $interval];

        // 1. Total calculations in range
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM user_calculations $where_clause");
        $stmt->execute($params);
        $totalInRange = (int) $stmt->fetchColumn();

        // 2. Average Step-Up % in range
        $stmt = $this->pdo->prepare("SELECT COALESCE(AVG(step_up_pct), 0) FROM user_calculations $where_clause AND step_up_pct > 0");
        $stmt->execute($params);
        $avgStepUp = (float) $stmt->fetchColumn();

        // 3. Total all-time calculations
        $totalAllTime = (int) $this->pdo->query("SELECT COUNT(*) FROM user_calculations")->fetchColumn();

        // 4. Calculations breakdown by type
        $stmt = $this->pdo->prepare("SELECT calc_type, COUNT(*) AS cnt FROM user_calculations $where_clause GROUP BY calc_type ORDER BY cnt DESC");
        $stmt->execute($params);
        $calcTypeBreakdown = $stmt->fetchAll();

        // 5. PDF Downloads count and conversion rate
        $totalPdfDownloads = 0;
        $conversionRate = 0.0;
        try {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM user_calculations $where_clause AND pdf_downloaded = 1");
            $stmt->execute($params);
            $totalPdfDownloads = (int) $stmt->fetchColumn();
            $conversionRate = $totalInRange > 0 ? round(($totalPdfDownloads / $totalInRange) * 100, 1) : 0.0;
        } catch (\Throwable $e) {
            error_log("InsightRepository Query Error (pdf_downloaded): " . $e->getMessage());
        }

        // 6. Top 10 Referrers in range
        $topReferrers = [];
        try {
            $stmt = $this->pdo->prepare("
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
            error_log("InsightRepository Query Error (referrer): " . $e->getMessage());
        }

        // 7. Chart: Daily volume
        $stmt = $this->pdo->prepare("
            SELECT DATE(created_at) AS day, COUNT(*) AS cnt
            FROM user_calculations
            $where_clause
            GROUP BY DATE(created_at)
            ORDER BY day ASC
        ");
        $stmt->execute($params);
        $dailyVolume = $stmt->fetchAll();

        // 8. Chart: Currency distribution
        $stmt = $this->pdo->prepare("
            SELECT UPPER(COALESCE(currency, 'UNKNOWN')) AS currency, COUNT(*) AS cnt
            FROM user_calculations
            $where_clause
            GROUP BY UPPER(COALESCE(currency, 'UNKNOWN'))
            ORDER BY cnt DESC
        ");
        $stmt->execute($params);
        $currencyDist = $stmt->fetchAll();

        // 9. Table: Top 10 SWP target corpus amounts
        $stmt = $this->pdo->prepare("
            SELECT amount, UPPER(COALESCE(currency, 'INR')) AS currency, COUNT(*) AS frequency
            FROM user_calculations
            $where_clause AND calc_type = 'SWP' AND amount IS NOT NULL
            GROUP BY amount, UPPER(COALESCE(currency, 'INR'))
            ORDER BY frequency DESC
            LIMIT 10
        ");
        $stmt->execute($params);
        $topCorpus = $stmt->fetchAll();

        // 10. Step-Up Adoption Rate metrics
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM user_calculations $where_clause AND calc_type = 'SIP'");
        $stmt->execute($params);
        $totalSIP = (int) $stmt->fetchColumn();

        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM user_calculations $where_clause AND calc_type = 'SIP' AND step_up_pct > 0");
        $stmt->execute($params);
        $stepUpSIP = (int) $stmt->fetchColumn();

        $flatSIP = $totalSIP - $stepUpSIP;
        $stepUpAdoptionRate = $totalSIP > 0 ? round(($stepUpSIP / $totalSIP) * 100, 1) : 0.0;

        // 11. Average Duration metrics
        $stmt = $this->pdo->prepare("SELECT COALESCE(AVG(duration), 0) FROM user_calculations $where_clause AND calc_type = 'SIP'");
        $stmt->execute($params);
        $avgDurationSIP = (float) $stmt->fetchColumn();

        $stmt = $this->pdo->prepare("SELECT COALESCE(AVG(duration), 0) FROM user_calculations $where_clause AND calc_type = 'SWP'");
        $stmt->execute($params);
        $avgDurationSWP = (float) $stmt->fetchColumn();

        // 12. Average Interest Rate
        $stmt = $this->pdo->prepare("SELECT COALESCE(AVG(interest_rate), 0) FROM user_calculations $where_clause AND interest_rate > 0");
        $stmt->execute($params);
        $avgInterestRate = (float) $stmt->fetchColumn();

        // 13. SWP Adoption Rate metrics
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM user_calculations $where_clause AND swp_enabled = 1");
        $stmt->execute($params);
        $totalSWPEnabled = (int) $stmt->fetchColumn();
        $swpAdoptionRate = $totalInRange > 0 ? round(($totalSWPEnabled / $totalInRange) * 100, 1) : 0.0;

        // 14. Average SIP and SWP Amounts
        $stmt = $this->pdo->prepare("SELECT COALESCE(AVG(sip_amount), 0) FROM user_calculations $where_clause AND sip_amount > 0");
        $stmt->execute($params);
        $avgSipAmount = (float) $stmt->fetchColumn();

        $stmt = $this->pdo->prepare("SELECT COALESCE(AVG(swp_withdrawal), 0) FROM user_calculations $where_clause AND swp_withdrawal > 0");
        $stmt->execute($params);
        $avgSwpWithdrawal = (float) $stmt->fetchColumn();

        // 15. Chart: Duration distribution buckets
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
            $where_clause AND duration IS NOT NULL
            GROUP BY bucket
            ORDER BY MIN(duration) ASC
        ");
        $stmt->execute($params);
        $durationDist = $stmt->fetchAll();

        // 16. Chart: Corpus Buckets (INR)
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
            $where_clause AND calc_type = 'SWP' AND amount IS NOT NULL AND UPPER(COALESCE(currency,'INR')) = 'INR'
            GROUP BY bucket
            ORDER BY MIN(amount) ASC
        ");
        $stmt->execute($params);
        $corpusBucketsINR = $stmt->fetchAll();

        // 17. Chart: Corpus Buckets (USD/Others)
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
            $where_clause AND calc_type = 'SWP' AND amount IS NOT NULL AND UPPER(COALESCE(currency,'INR')) != 'INR'
            GROUP BY bucket
            ORDER BY MIN(amount) ASC
        ");
        $stmt->execute($params);
        $corpusBucketsUSD = $stmt->fetchAll();

        // 18. Chart: Ambition Index buckets
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
            $where_clause AND amount IS NOT NULL
            GROUP BY goal_bucket
            ORDER BY MIN(amount) ASC
        ");
        $stmt->execute($params);
        $ambitionBuckets = $stmt->fetchAll();

        return [
            'totalInRange'       => $totalInRange,
            'avgStepUp'          => $avgStepUp,
            'totalAllTime'       => $totalAllTime,
            'calcTypeBreakdown'  => $calcTypeBreakdown,
            'totalPdfDownloads'  => $totalPdfDownloads,
            'conversionRate'     => $conversionRate,
            'topReferrers'       => $topReferrers,
            'dailyVolume'        => $dailyVolume,
            'currencyDist'       => $currencyDist,
            'topCorpus'          => $topCorpus,
            'totalSIP'           => $totalSIP,
            'stepUpSIP'          => $stepUpSIP,
            'flatSIP'            => $flatSIP,
            'stepUpAdoptionRate' => $stepUpAdoptionRate,
            'avgDurationSIP'     => $avgDurationSIP,
            'avgDurationSWP'     => $avgDurationSWP,
            'avgInterestRate'    => $avgInterestRate,
            'totalSWPEnabled'    => $totalSWPEnabled,
            'swpAdoptionRate'    => $swpAdoptionRate,
            'avgSipAmount'       => $avgSipAmount,
            'avgSwpWithdrawal'   => $avgSwpWithdrawal,
            'durationDist'       => $durationDist,
            'corpusBucketsINR'   => $corpusBucketsINR,
            'corpusBucketsUSD'   => $corpusBucketsUSD,
            'ambitionBuckets'    => $ambitionBuckets
        ];
    }
}
