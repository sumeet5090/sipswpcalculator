<?php
declare(strict_types=1);

/**
 * Privacy-First Anonymized Insight Logger
 * 
 * Logs anonymous usage statistics without storing IP addresses or PII.
 */
class AnonymizedInsightLogger
{
    private PDO $pdo;

    public function __construct(string $dbPath = __DIR__ . '/../database/database.sqlite')
    {
        // Ensure database directory exists
        $dir = dirname($dbPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $this->pdo = new PDO("sqlite:" . $dbPath);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->initializeSchema();
    }

    /**
     * Creates the required user_calculations schema if it doesn't exist.
     */
    private function initializeSchema(): void
    {
        $schema = "
            CREATE TABLE IF NOT EXISTS user_calculations (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                calc_type TEXT NOT NULL,
                currency TEXT,
                amount REAL,
                duration INTEGER,
                step_up_pct REAL,
                country_code TEXT,
                pdf_downloaded INTEGER DEFAULT 0,
                referrer TEXT,
                interest_rate REAL,
                sip_amount REAL,
                sip_duration INTEGER,
                sip_step_up REAL,
                swp_enabled INTEGER DEFAULT 0,
                swp_withdrawal REAL,
                swp_duration INTEGER,
                swp_step_up REAL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ";
        $this->pdo->exec($schema);

        // Migration: add columns to existing tables (safe no-op if already present)
        try { $this->pdo->exec("ALTER TABLE user_calculations ADD COLUMN pdf_downloaded INTEGER DEFAULT 0"); } catch (\Throwable $e) {}
        try { $this->pdo->exec("ALTER TABLE user_calculations ADD COLUMN referrer TEXT"); } catch (\Throwable $e) {}
        try { $this->pdo->exec("ALTER TABLE user_calculations ADD COLUMN interest_rate REAL"); } catch (\Throwable $e) {}
        try { $this->pdo->exec("ALTER TABLE user_calculations ADD COLUMN sip_amount REAL"); } catch (\Throwable $e) {}
        try { $this->pdo->exec("ALTER TABLE user_calculations ADD COLUMN sip_duration INTEGER"); } catch (\Throwable $e) {}
        try { $this->pdo->exec("ALTER TABLE user_calculations ADD COLUMN sip_step_up REAL"); } catch (\Throwable $e) {}
        try { $this->pdo->exec("ALTER TABLE user_calculations ADD COLUMN swp_enabled INTEGER DEFAULT 0"); } catch (\Throwable $e) {}
        try { $this->pdo->exec("ALTER TABLE user_calculations ADD COLUMN swp_withdrawal REAL"); } catch (\Throwable $e) {}
        try { $this->pdo->exec("ALTER TABLE user_calculations ADD COLUMN swp_duration INTEGER"); } catch (\Throwable $e) {}
        try { $this->pdo->exec("ALTER TABLE user_calculations ADD COLUMN swp_step_up REAL"); } catch (\Throwable $e) {}
    }

    /**
     * Executes the non-blocking logging logic.
     * 
     * CRITICAL: Must be called after the calculation results are displayed to the user
     * to avoid slowing down initial page load (0.7s LCP constraint).
     * 
     * @param string $calcType    Type of calculation: 'SIP', 'SWP', or 'DCA'
     * @param float  $amount      The primary investment or withdrawal amount
     * @param int    $duration    The duration in years
     * @param float  $stepUpPct   The step-up percentage (0.0 if not applicable)
     * @param string $currency    The chosen currency (e.g., INR, USD). Usually fetched from frontend state.
     */
    public function logCalculation(
        string $calcType,
        float $amount,
        int $duration,
        float $stepUpPct = 0.0,
        ?string $currency = null,
        bool $pdfDownloaded = false,
        ?float $interestRate = null,
        ?float $sipAmount = null,
        ?int $sipDuration = null,
        ?float $sipStepUp = null,
        int $swpEnabled = 0,
        ?float $swpWithdrawal = null,
        ?int $swpDuration = null,
        ?float $swpStepUp = null
        ): void
    {
        try {
            // Close the current output buffer and send response to client so logging is non-blocking.
            // This ensures LCP is not affected by database insert overhead.
            if (function_exists('fastcgi_finish_request')) {
                fastcgi_finish_request();
            }

            // Pull Cloudflare country code from server headers (Privacy-First: No IPs are logged)
            $countryCode = $_SERVER['HTTP_CF_IPCOUNTRY'] ?? null;

            // Capture HTTP Referrer for traffic-source analysis (truncated for privacy)
            $referrer = isset($_SERVER['HTTP_REFERER']) ? substr($_SERVER['HTTP_REFERER'], 0, 512) : null;

            // If currency is not explicitly passed, we could optionally default or look for it in headers/request
            if ($currency === null) {
                $currency = $_REQUEST['currency'] ?? null;
            }

            $stmt = $this->pdo->prepare("
                INSERT INTO user_calculations 
                (calc_type, currency, amount, duration, step_up_pct, country_code, pdf_downloaded, referrer,
                 interest_rate, sip_amount, sip_duration, sip_step_up, swp_enabled, swp_withdrawal, swp_duration, swp_step_up)
                VALUES (:calc_type, :currency, :amount, :duration, :step_up_pct, :country_code, :pdf_downloaded, :referrer,
                 :interest_rate, :sip_amount, :sip_duration, :sip_step_up, :swp_enabled, :swp_withdrawal, :swp_duration, :swp_step_up)
            ");

            $stmt->execute([
                ':calc_type' => $calcType,
                ':currency' => $currency,
                ':amount' => $amount,
                ':duration' => $duration,
                ':step_up_pct' => $stepUpPct,
                ':country_code' => $countryCode,
                ':pdf_downloaded' => $pdfDownloaded ? 1 : 0,
                ':referrer' => $referrer,
                ':interest_rate' => $interestRate,
                ':sip_amount' => $sipAmount,
                ':sip_duration' => $sipDuration,
                ':sip_step_up' => $sipStepUp,
                ':swp_enabled' => $swpEnabled,
                ':swp_withdrawal' => $swpWithdrawal,
                ':swp_duration' => $swpDuration,
                ':swp_step_up' => $swpStepUp,
            ]);

        }
        catch (\Throwable $e) {
            // Silently fail to ensure user experience is never impacted by logging errors
            error_log("AnonymizedInsightLogger Error: " . $e->getMessage());
        }
    }
}