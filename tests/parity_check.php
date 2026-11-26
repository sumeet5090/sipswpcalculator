<?php

/**
 * parity_check.php
 * Automated test script to compare backend (PHP) and frontend (JS) calculation engines.
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

// Define a test matrix of inputs
$testCases = [
    [
        'sip'            => 10000.0,
        'years'          => 15,
        'rate'           => 12.0,
        'stepup'         => 10.0,
        'enable_swp'     => false,
        'swp_withdrawal' => 0.0,
        'swp_stepup'     => 0.0,
        'swp_years'      => 0,
        'lumpsum'        => 0.0,
        'swp_rate'       => 8.0,
        'ltcg_exemption' => 125000.0,
        'ltcg_tax_rate'  => 0.125
    ],
    [
        'sip'            => 15000.0,
        'years'          => 10,
        'rate'           => 13.5,
        'stepup'         => 8.5,
        'enable_swp'     => true,
        'swp_withdrawal' => 50000.0,
        'swp_stepup'     => 6.0,
        'swp_years'      => 15,
        'lumpsum'        => 200000.0,
        'swp_rate'       => 8.2,
        'ltcg_exemption' => 125000.0,
        'ltcg_tax_rate'  => 0.125
    ],
    [
        'sip'            => 5000.0,
        'years'          => 20,
        'rate'           => 15.0,
        'stepup'         => 12.0,
        'enable_swp'     => true,
        'swp_withdrawal' => 80000.0,
        'swp_stepup'     => 7.5,
        'swp_years'      => 20,
        'lumpsum'        => 1000000.0,
        'swp_rate'       => 7.8,
        'ltcg_exemption' => 125000.0,
        'ltcg_tax_rate'  => 0.125
    ],
    // Extreme values
    [
        'sip'            => 50000.0,
        'years'          => 5,
        'rate'           => 25.0,
        'stepup'         => 15.0,
        'enable_swp'     => true,
        'swp_withdrawal' => 120000.0,
        'swp_stepup'     => 10.0,
        'swp_years'      => 10,
        'lumpsum'        => 5000000.0,
        'swp_rate'       => 9.0,
        'ltcg_exemption' => 125000.0,
        'ltcg_tax_rate'  => 0.125
    ],
    // Edge case: Year 0 Singularity
    [
        'sip'            => 0.0,
        'years'          => 0,
        'rate'           => 12.0,
        'stepup'         => 0.0,
        'enable_swp'     => false,
        'swp_withdrawal' => 0.0,
        'swp_stepup'     => 0.0,
        'swp_years'      => 0,
        'lumpsum'        => 100000.0,
        'swp_rate'       => 8.0,
        'ltcg_exemption' => 125000.0,
        'ltcg_tax_rate'  => 0.125
    ],
    // Edge case: Zero Rate & Zero Stepup
    [
        'sip'            => 10000.0,
        'years'          => 5,
        'rate'           => 0.0,
        'stepup'         => 0.0,
        'enable_swp'     => false,
        'swp_withdrawal' => 0.0,
        'swp_stepup'     => 0.0,
        'swp_years'      => 0,
        'lumpsum'        => 0.0,
        'swp_rate'       => 0.0,
        'ltcg_exemption' => 125000.0,
        'ltcg_tax_rate'  => 0.125
    ],
    // Edge case: Standalone SWP (0 SIP years, 15 SWP years)
    [
        'type'           => 'swp',
        'sip'            => 0.0,
        'years'          => 0,
        'rate'           => 12.0,
        'stepup'         => 0.0,
        'enable_swp'     => true,
        'swp_withdrawal' => 60000.0,
        'swp_stepup'     => 5.0,
        'swp_years'      => 15,
        'lumpsum'        => 10000000.0,
        'swp_rate'       => 7.5,
        'ltcg_exemption' => 125000.0,
        'ltcg_tax_rate'  => 0.125
    ],
    // Edge case: Early Depletion SWP (Depletes in Year 3 of 10)
    [
        'type'           => 'swp',
        'sip'            => 0.0,
        'years'          => 0,
        'rate'           => 10.0,
        'stepup'         => 0.0,
        'enable_swp'     => true,
        'swp_withdrawal' => 100000.0,
        'swp_stepup'     => 0.0,
        'swp_years'      => 10,
        'lumpsum'        => 2000000.0,
        'swp_rate'       => 6.0,
        'ltcg_exemption' => 125000.0,
        'ltcg_tax_rate'  => 0.125
    ],
    // Edge case: Pure Lumpsum Compounding
    [
        'type'           => 'lumpsum',
        'sip'            => 0.0,
        'years'          => 10,
        'rate'           => 14.0,
        'stepup'         => 0.0,
        'enable_swp'     => false,
        'swp_withdrawal' => 0.0,
        'swp_stepup'     => 0.0,
        'swp_years'      => 0,
        'lumpsum'        => 2500000.0,
        'swp_rate'       => 8.0,
        'ltcg_exemption' => 125000.0,
        'ltcg_tax_rate'  => 0.125
    ],
    // Edge case: Target Corpus High Accumulation 30-Year
    [
        'type'           => 'sip',
        'sip'            => 100000.0,
        'years'          => 30,
        'rate'           => 15.0,
        'stepup'         => 10.0,
        'enable_swp'     => false,
        'swp_withdrawal' => 0.0,
        'swp_stepup'     => 0.0,
        'swp_years'      => 0,
        'lumpsum'        => 500000.0,
        'swp_rate'       => 8.0,
        'ltcg_exemption' => 125000.0,
        'ltcg_tax_rate'  => 0.125
    ],
    // Edge case: Max Step-Up Boundary (50% annual stepup)
    [
        'type'           => 'sip',
        'sip'            => 10000.0,
        'years'          => 10,
        'rate'           => 25.0,
        'stepup'         => 50.0,
        'enable_swp'     => false,
        'swp_withdrawal' => 0.0,
        'swp_stepup'     => 0.0,
        'swp_years'      => 0,
        'lumpsum'        => 0.0,
        'swp_rate'       => 8.0,
        'ltcg_exemption' => 125000.0,
        'ltcg_tax_rate'  => 0.125
    ],
    // Edge case: Dual Regime High Inflation & High Rate
    [
        'type'           => 'sip_swp',
        'sip'            => 25000.0,
        'years'          => 15,
        'rate'           => 18.0,
        'stepup'         => 10.0,
        'enable_swp'     => true,
        'swp_withdrawal' => 75000.0,
        'swp_stepup'     => 8.0,
        'swp_years'      => 15,
        'lumpsum'        => 1000000.0,
        'swp_rate'       => 12.0,
        'ltcg_exemption' => 125000.0,
        'ltcg_tax_rate'  => 0.125
    ],
    // Edge case: Micro-SIP (₹500, 1-year horizon, 1% rate)
    [
        'type'           => 'sip',
        'sip'            => 500.0,
        'years'          => 1,
        'rate'           => 1.0,
        'stepup'         => 0.0,
        'enable_swp'     => false,
        'swp_withdrawal' => 0.0,
        'swp_stepup'     => 0.0,
        'swp_years'      => 0,
        'lumpsum'        => 0.0,
        'swp_rate'       => 8.0,
        'ltcg_exemption' => 125000.0,
        'ltcg_tax_rate'  => 0.125
    ],
    // Edge case: Ultra-High Wealth (₹10 Lakh/mo, 40 yrs, 25% CAGR, 20% stepup, ₹1 Cr lumpsum)
    [
        'type'           => 'sip',
        'sip'            => 1000000.0,
        'years'          => 40,
        'rate'           => 25.0,
        'stepup'         => 20.0,
        'enable_swp'     => false,
        'swp_withdrawal' => 0.0,
        'swp_stepup'     => 0.0,
        'swp_years'      => 0,
        'lumpsum'        => 10000000.0,
        'swp_rate'       => 8.0,
        'ltcg_exemption' => 125000.0,
        'ltcg_tax_rate'  => 0.125
    ],
    // Edge case: Rapid SWP Depletion (₹2 Lakh/mo with ₹10 Lakh corpus, exhausts in <1 yr)
    [
        'type'           => 'swp',
        'sip'            => 0.0,
        'years'          => 0,
        'rate'           => 6.0,
        'stepup'         => 0.0,
        'enable_swp'     => true,
        'swp_withdrawal' => 200000.0,
        'swp_stepup'     => 0.0,
        'swp_years'      => 10,
        'lumpsum'        => 1000000.0,
        'swp_rate'       => 6.0,
        'ltcg_exemption' => 125000.0,
        'ltcg_tax_rate'  => 0.125
    ],
    // Guide Scenario B (₹20k/mo, 10% Stepup, 15 yrs @ 12%)
    [
        'type'           => 'sip',
        'sip'            => 20000.0,
        'years'          => 15,
        'rate'           => 12.0,
        'stepup'         => 10.0,
        'enable_swp'     => false,
        'swp_withdrawal' => 0.0,
        'swp_stepup'     => 0.0,
        'swp_years'      => 0,
        'lumpsum'        => 0.0,
        'swp_rate'       => 8.0,
        'ltcg_exemption' => 125000.0,
        'ltcg_tax_rate'  => 0.125
    ],
    // Guide Step-Up 5% (₹10k/mo, 5% Stepup, 20 yrs @ 12%)
    [
        'type'           => 'sip',
        'sip'            => 10000.0,
        'years'          => 20,
        'rate'           => 12.0,
        'stepup'         => 5.0,
        'enable_swp'     => false,
        'swp_withdrawal' => 0.0,
        'swp_stepup'     => 0.0,
        'swp_years'      => 0,
        'lumpsum'        => 0.0,
        'swp_rate'       => 8.0,
        'ltcg_exemption' => 125000.0,
        'ltcg_tax_rate'  => 0.125
    ],
    // Guide SWP Plan 2 (1 Cr corpus, ₹45k/mo, 25 yrs @ 8% return, 5% hike)
    [
        'type'           => 'swp',
        'sip'            => 0.0,
        'years'          => 0,
        'rate'           => 8.0,
        'stepup'         => 0.0,
        'enable_swp'     => true,
        'swp_withdrawal' => 45000.0,
        'swp_stepup'     => 5.0,
        'swp_years'      => 25,
        'lumpsum'        => 10000000.0,
        'swp_rate'       => 8.0,
        'ltcg_exemption' => 125000.0,
        'ltcg_tax_rate'  => 0.125
    ],
    // Case 19: 40-Year Ultra-Horizon Compounding (₹25k/mo, 10% Stepup, 40 yrs @ 12%)
    [
        'type'           => 'sip',
        'sip'            => 25000.0,
        'years'          => 40,
        'rate'           => 12.0,
        'stepup'         => 10.0,
        'enable_swp'     => false,
        'swp_withdrawal' => 0.0,
        'swp_stepup'     => 0.0,
        'swp_years'      => 0,
        'lumpsum'        => 0.0,
        'swp_rate'       => 8.0,
        'ltcg_exemption' => 125000.0,
        'ltcg_tax_rate'  => 0.125
    ],
    // Case 20: 20-Yr Accumulation + 20-Yr SWP Retirement (₹50k/mo SIP -> ₹1.5L/mo SWP)
    [
        'type'           => 'sip_swp',
        'sip'            => 50000.0,
        'years'          => 20,
        'rate'           => 13.0,
        'stepup'         => 8.0,
        'enable_swp'     => true,
        'swp_withdrawal' => 150000.0,
        'swp_stepup'     => 5.0,
        'swp_years'      => 20,
        'lumpsum'        => 500000.0,
        'swp_rate'       => 8.5,
        'ltcg_exemption' => 125000.0,
        'ltcg_tax_rate'  => 0.125
    ]
];

$failed = false;

echo "=== Running Calculator Parity Checks ===\n\n";

foreach ($testCases as $index => $inputs) {
    echo "Running Test Case #" . ($index + 1) . "... ";

    // 1. PHP calculations
    $configService = new \Services\ConfigService(__DIR__ . '/../content/calculator_defaults.json');
    if (($inputs['type'] ?? '') === 'swp') {
        $dto = \Core\InvestmentInputs::fromSwpRequest([
            'corpus'         => $inputs['lumpsum'],
            'swp_withdrawal' => $inputs['swp_withdrawal'],
            'swp_stepup'     => $inputs['swp_stepup'],
            'swp_years'      => $inputs['swp_years'],
            'swp_rate'       => $inputs['swp_rate'],
            'inflation'      => 0.0
        ], $configService);
    } else {
        $dto = \Core\InvestmentInputs::fromRequest([
            'sip'            => $inputs['sip'],
            'years'          => $inputs['years'],
            'rate'           => $inputs['rate'],
            'stepup'         => $inputs['stepup'],
            'enable_swp'     => $inputs['enable_swp'],
            'swp_withdrawal' => $inputs['swp_withdrawal'],
            'swp_stepup'     => $inputs['swp_stepup'],
            'swp_years'      => $inputs['swp_years'],
            'lumpsum'        => $inputs['lumpsum'],
            'swp_rate'       => $inputs['swp_rate'],
            'ltcg_exemption' => $inputs['ltcg_exemption'],
            'ltcg_tax_rate'  => $inputs['ltcg_tax_rate'],
        ], $configService);
    }

    $phpCalc = new \Core\InvestmentCalculator();
    $phpResults = $phpCalc->calculate($dto);

    // 2. JS calculations
    $escapedJson = escapeshellarg(json_encode([
        'sip'            => $dto->getSip(),
        'years'          => $dto->getYears(),
        'rate'           => $dto->getRate(),
        'stepup'         => $dto->getStepup(),
        'enable_swp'     => $dto->isSwpEnabled(),
        'swp_withdrawal' => $dto->getSwpWithdrawal(),
        'swp_stepup'     => $dto->getSwpStepup(),
        'swp_years'      => $dto->getSwpYears(),
        'lumpsum'        => $dto->getLumpsum(),
        'swp_rate'       => $dto->getSwpRate(),
        'ltcg_exemption' => $dto->getLtcgExemption(),
        'ltcg_tax_rate'  => $dto->getLtcgTaxRate(),
    ]));

    $cmd = "node " . escapeshellarg(__DIR__ . '/run_js_calc.js') . " {$escapedJson}";
    $jsOutputRaw = shell_exec($cmd);

    if (!$jsOutputRaw) {
        echo "FAIL: Node runner failed or outputted empty response.\n";
        $failed = true;
        continue;
    }

    $jsResults = json_decode($jsOutputRaw, true);
    if (!is_array($jsResults)) {
        echo "FAIL: Node runner outputted invalid JSON format: {$jsOutputRaw}\n";
        $failed = true;
        continue;
    }

    // 3. Row count validation
    if (count($phpResults) !== count($jsResults)) {
        echo "FAIL: Row count mismatch (PHP Count: " . count($phpResults) . ", JS Count: " . count($jsResults) . ")\n";
        $failed = true;
        continue;
    }

    $mismatch = false;
    foreach ($phpResults as $rowIdx => $phpRow) {
        $jsRow = $jsResults[$rowIdx];

        $fields = [
            'year',
            'begin_balance',
            'sip_monthly',
            'annual_contribution',
            'cumulative_invested',
            'swp_monthly',
            'annual_withdrawal',
            'cumulative_withdrawals',
            'interest',
            'combined_total',
            'ltcg_tax',
            'post_tax_total'
        ];

        foreach ($fields as $field) {
            $phpVal = $phpRow[$field];
            $jsVal = $jsRow[$field];

            if ($phpVal !== null && $jsVal !== null) {
                // Assert float value parity within tolerance (accounting for huge scales)
                $tolerance = max(0.05, abs((float)$phpVal * 0.00001));
                if (abs((float)$phpVal - (float)$jsVal) > $tolerance) {
                    echo "FAIL: Parity drift at row {$rowIdx}, field '{$field}'. PHP: '{$phpVal}', JS: '{$jsVal}'\n";
                    $mismatch = true;
                    $failed = true;
                    break 2;
                }
            } else {
                if ($phpVal !== $jsVal) {
                    echo "FAIL: Nullness mismatch at row {$rowIdx}, field '{$field}'. PHP: " . var_export($phpVal, true) . ", JS: " . var_export($jsVal, true) . "\n";
                    $mismatch = true;
                    $failed = true;
                    break 2;
                }
            }
        }
    }

    if (!$mismatch) {
        echo "PASS\n";
    }
}

// 4. Binary Search & Helper Parity Tests
echo "\n=== Running Inverse Binary Search & Helper Math Parity ===\n";

$helperTests = [
    [
        'name' => 'Required SIP for ₹1 Crore Target (15 yrs @ 12%, 10% stepup)',
        'action' => 'required_sip',
        'inputs' => \Core\InvestmentInputs::fromValues(0.0, 15, 12.0, 10.0, false, 0.0, 0.0, 0, 0.0),
        'target_corpus' => 10000000.0,
    ],
    [
        'name' => 'Required SWP Starting Corpus (₹50k/mo, 20 yrs @ 8%, 5% hike)',
        'action' => 'swp_required_corpus',
        'inputs' => \Core\InvestmentInputs::fromValues(0.0, 0, 0.0, 0.0, true, 50000.0, 5.0, 20, 0.0, 8.0),
    ],
    [
        'name' => 'Inflation Discounting (₹1 Crore over 20 yrs @ 6% inflation)',
        'action' => 'inflation_discount',
        'corpus' => 10000000.0,
        'years' => 20,
        'inflation' => 6.0,
    ],
    [
        'name' => '1-Year Delay Cost (₹25k SIP, 15 yrs @ 12%, 10% stepup)',
        'action' => 'delay_cost',
        'inputs' => \Core\InvestmentInputs::fromValues(25000.0, 15, 12.0, 10.0, false, 0.0, 0.0, 0, 0.0),
    ],
    [
        'name' => 'Safe SWP Monthly Withdrawal (₹1 Crore corpus, 20 yrs @ 8%, 5% stepup)',
        'action' => 'safe_swp_withdrawal',
        'inputs' => \Core\InvestmentInputs::fromValues(0.0, 0, 0.0, 0.0, true, 0.0, 5.0, 20, 10000000.0, 8.0),
        'starting_corpus' => 10000000.0
    ]
];

foreach ($helperTests as $hTest) {
    echo "Running {$hTest['name']}... ";

    $phpVal = 0.0;
    $jsPayload = ['action' => $hTest['action']];

    if ($hTest['action'] === 'required_sip') {
        $phpVal = $phpCalc->calculateRequiredSip($hTest['inputs'], $hTest['target_corpus']);
        $jsPayload['inputs'] = [
            'sip' => 0,
            'years' => $hTest['inputs']->getYears(),
            'rate' => $hTest['inputs']->getRate(),
            'stepup' => $hTest['inputs']->getStepup(),
            'lumpsum' => $hTest['inputs']->getLumpsum(),
            'enable_swp' => false,
            'swp_withdrawal' => 0,
            'swp_years' => 0,
            'swp_stepup' => 0,
            'swp_rate' => 8
        ];
        $jsPayload['target_corpus'] = $hTest['target_corpus'];
    } elseif ($hTest['action'] === 'swp_required_corpus') {
        $phpVal = $phpCalc->calculateRequiredStartingCorpusForSwp($hTest['inputs']);
        $jsPayload['inputs'] = [
            'sip' => 0,
            'years' => 0,
            'rate' => 0,
            'stepup' => 0,
            'lumpsum' => 0,
            'enable_swp' => true,
            'swp_withdrawal' => $hTest['inputs']->getSwpWithdrawal(),
            'swp_years' => $hTest['inputs']->getSwpYears(),
            'swp_stepup' => $hTest['inputs']->getSwpStepup(),
            'swp_rate' => $hTest['inputs']->getSwpRate()
        ];
    } elseif ($hTest['action'] === 'inflation_discount') {
        $phpVal = \Core\InvestmentCalculator::calculateInflationDiscount($hTest['corpus'], $hTest['years'], $hTest['inflation']);
        $jsPayload['corpus'] = $hTest['corpus'];
        $jsPayload['years'] = $hTest['years'];
        $jsPayload['inflation'] = $hTest['inflation'];
    } elseif ($hTest['action'] === 'delay_cost') {
        $phpVal = $phpCalc->calculateDelayCost($hTest['inputs']);
        $jsPayload['inputs'] = [
            'sip' => $hTest['inputs']->getSip(),
            'years' => $hTest['inputs']->getYears(),
            'rate' => $hTest['inputs']->getRate(),
            'stepup' => $hTest['inputs']->getStepup(),
            'lumpsum' => $hTest['inputs']->getLumpsum(),
            'enable_swp' => false,
            'swp_withdrawal' => 0,
            'swp_years' => 0,
            'swp_stepup' => 0,
            'swp_rate' => 8
        ];
    } elseif ($hTest['action'] === 'safe_swp_withdrawal') {
        $phpVal = $phpCalc->calculateSafeSwpWithdrawal($hTest['inputs'], $hTest['starting_corpus']);
        $jsPayload['inputs'] = [
            'sip' => 0,
            'years' => 0,
            'rate' => 0,
            'stepup' => 0,
            'lumpsum' => $hTest['starting_corpus'],
            'enable_swp' => true,
            'swp_withdrawal' => 0,
            'swp_years' => $hTest['inputs']->getSwpYears(),
            'swp_stepup' => $hTest['inputs']->getSwpStepup(),
            'swp_rate' => $hTest['inputs']->getSwpRate()
        ];
        $jsPayload['starting_corpus'] = $hTest['starting_corpus'];
    }

    $escapedJson = escapeshellarg(json_encode($jsPayload));
    $cmd = "node " . escapeshellarg(__DIR__ . '/run_js_calc.js') . " {$escapedJson}";
    $jsOutputRaw = shell_exec($cmd);
    $jsRes = json_decode((string) $jsOutputRaw, true);
    $jsVal = (float) ($jsRes['result'] ?? -999999);

    if (abs($phpVal - $jsVal) <= 1.0) {
        echo "PASS (PHP: {$phpVal}, JS: {$jsVal})\n";
    } else {
        echo "FAIL (PHP: {$phpVal}, JS: {$jsVal})\n";
        $failed = true;
    }
}

// 5. Specialized Math Engine Parity Checks
echo "\n=== Running Specialized Math Engine Parity (CI, CAGR, EMI, Inflation) ===\n";

// A. Compound Interest Parity
$ciEngine = new \Core\Math\CompoundInterestEngine();
$ciCases = [
    ['principal' => 100000.0, 'annual_rate' => 10.0, 'years' => 5, 'compounding_frequency' => 1],
    ['principal' => 50000.0, 'annual_rate' => 12.0, 'years' => 3, 'compounding_frequency' => 12],
    ['principal' => 200000.0, 'annual_rate' => 8.0, 'years' => 2, 'compounding_frequency' => 4],
    ['principal' => 1500000.0, 'annual_rate' => 14.5, 'years' => 15, 'compounding_frequency' => 1],
    ['principal' => 1000000.0, 'annual_rate' => 7.0, 'years' => 1, 'compounding_frequency' => 365],
    ['principal' => 500000.0, 'annual_rate' => 9.0, 'years' => 3, 'compounding_frequency' => 2],
    ['principal' => 750000.0, 'annual_rate' => 8.375, 'years' => 4, 'compounding_frequency' => 12],
    ['principal' => 1000000.0, 'annual_rate' => 12.0, 'years' => 30, 'compounding_frequency' => 12]
];

foreach ($ciCases as $idx => $ciParams) {
    echo "Running Compound Interest Case #" . ($idx + 1) . "... ";
    $phpRes = $ciEngine->calculate(
        $ciParams['principal'],
        $ciParams['annual_rate'],
        $ciParams['years'],
        $ciParams['compounding_frequency']
    );

    $cmd = "node " . escapeshellarg(__DIR__ . '/run_js_calc.js') . " " . escapeshellarg(json_encode([
        'action' => 'compound_interest',
        'principal' => $ciParams['principal'],
        'annual_rate' => $ciParams['annual_rate'],
        'years' => $ciParams['years'],
        'compounding_frequency' => $ciParams['compounding_frequency']
    ]));
    $jsRes = json_decode((string)shell_exec($cmd), true);

    $fields = ['principal', 'final_amount', 'total_interest', 'effective_annual_rate'];
    $mismatch = false;
    foreach ($fields as $f) {
        if (abs((float)$phpRes[$f] - (float)$jsRes[$f]) > 0.05) {
            echo "FAIL: Field {$f} mismatch. PHP: {$phpRes[$f]}, JS: {$jsRes[$f]}\n";
            $failed = true;
            $mismatch = true;
            break;
        }
    }
    if (!$mismatch) {
        echo "PASS\n";
    }
}

// B. CAGR Parity
$cagrEngine = new \Core\Math\CagrEngine();
$cagrCases = [
    ['beginning_value' => 100000.0, 'ending_value' => 200000.0, 'years' => 5.0],
    ['beginning_value' => 50000.0, 'ending_value' => 75000.0, 'years' => 2.5],
    ['beginning_value' => 100000.0, 'ending_value' => 60000.0, 'years' => 3.0],
    ['beginning_value' => 2500000.0, 'ending_value' => 15000000.0, 'years' => 12.0],
    ['beginning_value' => 100000.0, 'ending_value' => 100000.0, 'years' => 5.0],
    ['beginning_value' => 100000.0, 'ending_value' => 5000000.0, 'years' => 7.0],
    ['beginning_value' => 100000.0, 'ending_value' => 105000.0, 'years' => 0.25],
    ['beginning_value' => 100000.0, 'ending_value' => 1.0, 'years' => 5.0]
];

foreach ($cagrCases as $idx => $cagrParams) {
    echo "Running CAGR Case #" . ($idx + 1) . "... ";
    $phpRes = $cagrEngine->calculate(
        $cagrParams['beginning_value'],
        $cagrParams['ending_value'],
        $cagrParams['years']
    );

    $cmd = "node " . escapeshellarg(__DIR__ . '/run_js_calc.js') . " " . escapeshellarg(json_encode([
        'action' => 'cagr',
        'beginning_value' => $cagrParams['beginning_value'],
        'ending_value' => $cagrParams['ending_value'],
        'years' => $cagrParams['years']
    ]));
    $jsRes = json_decode((string)shell_exec($cmd), true);

    $fields = ['cagr_percentage', 'absolute_return_percentage', 'total_gain', 'multiplier'];
    $mismatch = false;
    foreach ($fields as $f) {
        if (abs((float)$phpRes[$f] - (float)$jsRes[$f]) > 0.05) {
            echo "FAIL: Field {$f} mismatch. PHP: {$phpRes[$f]}, JS: {$jsRes[$f]}\n";
            $failed = true;
            $mismatch = true;
            break;
        }
    }
    if (!$mismatch) {
        echo "PASS\n";
    }
}

// C. EMI Parity
$emiEngine = new \Core\Math\EmiEngine();
$emiCases = [
    ['principal' => 2500000.0, 'annual_rate' => 8.5, 'tenure_years' => 20],
    ['principal' => 800000.0, 'annual_rate' => 9.2, 'tenure_years' => 7],
    ['principal' => 500000.0, 'annual_rate' => 11.5, 'tenure_years' => 3],
    ['principal' => 5000000.0, 'annual_rate' => 8.0, 'tenure_years' => 30],
    ['principal' => 600000.0, 'annual_rate' => 12.0, 'tenure_years' => 1],
    ['principal' => 500000.0, 'annual_rate' => 21.0, 'tenure_years' => 3],
    ['principal' => 1000000.0, 'annual_rate' => 4.5, 'tenure_years' => 10],
    ['principal' => 100000000.0, 'annual_rate' => 8.75, 'tenure_years' => 15]
];

foreach ($emiCases as $idx => $emiParams) {
    echo "Running EMI Case #" . ($idx + 1) . "... ";
    $phpRes = $emiEngine->calculate(
        $emiParams['principal'],
        $emiParams['annual_rate'],
        $emiParams['tenure_years']
    );

    $cmd = "node " . escapeshellarg(__DIR__ . '/run_js_calc.js') . " " . escapeshellarg(json_encode([
        'action' => 'emi',
        'principal' => $emiParams['principal'],
        'annual_rate' => $emiParams['annual_rate'],
        'tenure_years' => $emiParams['tenure_years']
    ]));
    $jsRes = json_decode((string)shell_exec($cmd), true);

    $fields = ['monthly_emi', 'total_amount_payable', 'total_interest', 'interest_ratio_percentage'];
    $mismatch = false;
    foreach ($fields as $f) {
        if (abs((float)$phpRes[$f] - (float)$jsRes[$f]) > 0.05) {
            echo "FAIL: Field {$f} mismatch. PHP: {$phpRes[$f]}, JS: {$jsRes[$f]}\n";
            $failed = true;
            $mismatch = true;
            break;
        }
    }
    if (!$mismatch) {
        echo "PASS\n";
    }
}

// D. Inflation Parity
$inflationEngine = new \Core\Math\InflationEngine();
$inflationCases = [
    ['present_value' => 1000000.0, 'inflation_rate' => 6.0, 'years' => 10],
    ['present_value' => 500000.0, 'inflation_rate' => 7.0, 'years' => 20],
    ['present_value' => 2500000.0, 'inflation_rate' => 5.5, 'years' => 25],
    ['present_value' => 50000.0, 'inflation_rate' => 8.0, 'years' => 5],
    ['present_value' => 100000.0, 'inflation_rate' => 20.0, 'years' => 10],
    ['present_value' => 50000.0, 'inflation_rate' => 6.5, 'years' => 40],
    ['present_value' => 250000.0, 'inflation_rate' => 5.85, 'years' => 15]
];

foreach ($inflationCases as $idx => $infParams) {
    echo "Running Inflation Case #" . ($idx + 1) . "... ";
    $phpRes = $inflationEngine->calculate(
        $infParams['present_value'],
        $infParams['inflation_rate'],
        $infParams['years']
    );

    $cmd = "node " . escapeshellarg(__DIR__ . '/run_js_calc.js') . " " . escapeshellarg(json_encode([
        'action' => 'inflation',
        'present_value' => $infParams['present_value'],
        'inflation_rate' => $infParams['inflation_rate'],
        'years' => $infParams['years']
    ]));
    $jsRes = json_decode((string)shell_exec($cmd), true);

    $fields = ['future_cost', 'purchasing_power', 'cost_increase', 'purchasing_power_loss_percentage'];
    $mismatch = false;
    foreach ($fields as $f) {
        if (abs((float)$phpRes[$f] - (float)$jsRes[$f]) > 0.05) {
            echo "FAIL: Field {$f} mismatch. PHP: {$phpRes[$f]}, JS: {$jsRes[$f]}\n";
            $failed = true;
            $mismatch = true;
            break;
        }
    }
    if (!$mismatch) {
        echo "PASS\n";
    }
}

// -------------------------------------------------------------
// 6. Cross-Runtime Parity: PPF Engine
// -------------------------------------------------------------
echo "\nTesting PPF Engine Parity (PHP vs TypeScript)...\n";
$ppfCases = [
    ['yearly_deposit' => 150000.0, 'interest_rate' => 7.1, 'tenure_years' => 15, 'deposit_timing' => 'beginning'],
    ['yearly_deposit' => 50000.0, 'interest_rate' => 7.1, 'tenure_years' => 20, 'deposit_timing' => 'beginning'],
    ['yearly_deposit' => 120000.0, 'interest_rate' => 7.1, 'tenure_years' => 15, 'deposit_timing' => 'monthly'],
    ['yearly_deposit' => 500.0, 'interest_rate' => 7.1, 'tenure_years' => 15, 'deposit_timing' => 'beginning'],
    ['yearly_deposit' => 150000.0, 'interest_rate' => 7.1, 'tenure_years' => 35, 'deposit_timing' => 'beginning'],
    ['yearly_deposit' => 100000.0, 'interest_rate' => 8.0, 'tenure_years' => 15, 'deposit_timing' => 'monthly']
];

foreach ($ppfCases as $idx => $pParams) {
    echo "PPF Parity Case " . ($idx + 1) . ": ";
    $phpRes = \Core\Math\PpfEngine::calculate(
        $pParams['yearly_deposit'],
        $pParams['interest_rate'],
        $pParams['tenure_years'],
        $pParams['deposit_timing']
    );

    $cmd = "node tests/run_js_calc.js " . escapeshellarg(json_encode([
        'action' => 'ppf',
        'yearly_deposit' => $pParams['yearly_deposit'],
        'interest_rate' => $pParams['interest_rate'],
        'tenure_years' => $pParams['tenure_years'],
        'deposit_timing' => $pParams['deposit_timing']
    ]));
    $jsRes = json_decode((string)shell_exec($cmd), true);

    $fields = ['total_invested', 'total_interest', 'maturity_amount'];
    $mismatch = false;
    foreach ($fields as $f) {
        if (abs((float)$phpRes[$f] - (float)$jsRes[$f]) > 0.05) {
            echo "FAIL: Field {$f} mismatch. PHP: {$phpRes[$f]}, JS: {$jsRes[$f]}\n";
            $failed = true;
            $mismatch = true;
            break;
        }
    }
    if (!$mismatch) {
        echo "PASS\n";
    }
}

// -------------------------------------------------------------
// 7. Cross-Runtime Parity: FD Engine
// -------------------------------------------------------------
echo "\nTesting Fixed Deposit (FD) Engine Parity (PHP vs TypeScript)...\n";
$fdCases = [
    ['principal' => 100000.0, 'annual_rate' => 7.0, 'duration_years' => 1.0, 'is_senior_citizen' => false, 'payout_frequency' => 'cumulative'],
    ['principal' => 500000.0, 'annual_rate' => 7.5, 'duration_years' => 3.0, 'is_senior_citizen' => true, 'payout_frequency' => 'cumulative'],
    ['principal' => 1000000.0, 'annual_rate' => 8.0, 'duration_years' => 2.0, 'is_senior_citizen' => false, 'payout_frequency' => 'quarterly'],
    ['principal' => 200000.0, 'annual_rate' => 6.5, 'duration_years' => 5.0, 'is_senior_citizen' => false, 'payout_frequency' => 'monthly'],
    ['principal' => 1200000.0, 'annual_rate' => 7.5, 'duration_years' => 2.0, 'is_senior_citizen' => false, 'payout_frequency' => 'monthly'],
    ['principal' => 1000000.0, 'annual_rate' => 7.0, 'duration_years' => 3.0, 'is_senior_citizen' => false, 'payout_frequency' => 'annual'],
    ['principal' => 800000.0, 'annual_rate' => 7.0, 'duration_years' => 1.0, 'is_senior_citizen' => true, 'payout_frequency' => 'cumulative'],
    ['principal' => 100000.0, 'annual_rate' => 8.0, 'duration_years' => 0.25, 'is_senior_citizen' => false, 'payout_frequency' => 'cumulative'],
    ['principal' => 1000000.0, 'annual_rate' => 7.0, 'duration_years' => 10.0, 'is_senior_citizen' => false, 'payout_frequency' => 'cumulative']
];

foreach ($fdCases as $idx => $fParams) {
    echo "FD Parity Case " . ($idx + 1) . ": ";
    $phpRes = \Core\Math\FdEngine::calculate(
        $fParams['principal'],
        $fParams['annual_rate'],
        $fParams['duration_years'],
        $fParams['is_senior_citizen'],
        $fParams['payout_frequency']
    );

    $cmd = "node tests/run_js_calc.js " . escapeshellarg(json_encode([
        'action' => 'fd',
        'principal' => $fParams['principal'],
        'annual_rate' => $fParams['annual_rate'],
        'duration_years' => $fParams['duration_years'],
        'is_senior_citizen' => $fParams['is_senior_citizen'],
        'payout_frequency' => $fParams['payout_frequency']
    ]));
    $jsRes = json_decode((string)shell_exec($cmd), true);

    $fields = ['principal', 'effective_rate', 'maturity_amount', 'total_interest', 'periodic_payout', 'estimated_annual_tds'];
    $mismatch = false;
    foreach ($fields as $f) {
        if (abs((float)$phpRes[$f] - (float)$jsRes[$f]) > 0.05) {
            echo "FAIL: Field {$f} mismatch. PHP: {$phpRes[$f]}, JS: {$jsRes[$f]}\n";
            $failed = true;
            $mismatch = true;
            break;
        }
    }
    if (!$mismatch) {
        echo "PASS\n";
    }
}

// -------------------------------------------------------------
// 8. Cross-Runtime Parity: Gratuity Engine
// -------------------------------------------------------------
echo "\nTesting Statutory Gratuity Engine Parity (PHP vs TypeScript)...\n";
$gratuityCases = [
    ['monthly_salary' => 50000.0, 'tenure_years' => 5, 'tenure_months' => 0, 'is_covered_by_act' => true],
    ['monthly_salary' => 50000.0, 'tenure_years' => 5, 'tenure_months' => 7, 'is_covered_by_act' => true], // >6M rounds up
    ['monthly_salary' => 50000.0, 'tenure_years' => 5, 'tenure_months' => 6, 'is_covered_by_act' => true], // <=6M rounds down
    ['monthly_salary' => 50000.0, 'tenure_years' => 5, 'tenure_months' => 7, 'is_covered_by_act' => false], // Not covered (no round)
    ['monthly_salary' => 60000.0, 'tenure_years' => 3, 'tenure_months' => 0, 'is_covered_by_act' => true], // Below 5y vesting (₹0)
    ['monthly_salary' => 150000.0, 'tenure_years' => 30, 'tenure_months' => 0, 'is_covered_by_act' => true], // Above ₹20L cap
    ['monthly_salary' => 300000.0, 'tenure_years' => 15, 'tenure_months' => 8, 'is_covered_by_act' => true], // High earner
    ['monthly_salary' => 25000.0, 'tenure_years' => 10, 'tenure_months' => 4, 'is_covered_by_act' => true],
];

foreach ($gratuityCases as $idx => $gParams) {
    echo "Gratuity Parity Case " . ($idx + 1) . ": ";
    $phpRes = \Core\Math\GratuityEngine::calculate(
        $gParams['monthly_salary'],
        $gParams['tenure_years'],
        $gParams['tenure_months'],
        $gParams['is_covered_by_act']
    );

    $cmd = "node tests/run_js_calc.js " . escapeshellarg(json_encode([
        'action' => 'gratuity',
        'monthly_salary' => $gParams['monthly_salary'],
        'tenure_years' => $gParams['tenure_years'],
        'tenure_months' => $gParams['tenure_months'],
        'is_covered_by_act' => $gParams['is_covered_by_act']
    ]));
    $jsRes = json_decode((string)shell_exec($cmd), true);

    $fields = ['monthly_salary', 'effective_years', 'gratuity_amount', 'tax_exempt_amount', 'taxable_amount'];
    $mismatch = false;
    foreach ($fields as $f) {
        if (abs((float)$phpRes[$f] - (float)$jsRes[$f]) > 0.05) {
            echo "FAIL: Field {$f} mismatch. PHP: {$phpRes[$f]}, JS: {$jsRes[$f]}\n";
            $failed = true;
            $mismatch = true;
            break;
        }
    }
    if (!$mismatch) {
        echo "PASS\n";
    }
}

// 9. Cross-Runtime Parity: National Pension System (NPS) Engine
// -------------------------------------------------------------
echo "\nTesting National Pension System (NPS) Engine Parity (PHP vs TypeScript)...\n";
$npsCases = [
    ['monthly_contribution' => 5000.0, 'current_age' => 30, 'retirement_age' => 60, 'expected_return_rate' => 10.0, 'annuity_percent' => 40.0, 'annuity_return_rate' => 6.0],
    ['monthly_contribution' => 25000.0, 'current_age' => 25, 'retirement_age' => 60, 'expected_return_rate' => 12.0, 'annuity_percent' => 40.0, 'annuity_return_rate' => 6.5],
    ['monthly_contribution' => 15000.0, 'current_age' => 45, 'retirement_age' => 60, 'expected_return_rate' => 9.0, 'annuity_percent' => 50.0, 'annuity_return_rate' => 6.0],
    ['monthly_contribution' => 10000.0, 'current_age' => 40, 'retirement_age' => 60, 'expected_return_rate' => 10.0, 'annuity_percent' => 100.0, 'annuity_return_rate' => 6.0],
    ['monthly_contribution' => 500.0, 'current_age' => 58, 'retirement_age' => 60, 'expected_return_rate' => 5.0, 'annuity_percent' => 40.0, 'annuity_return_rate' => 6.0],
    ['monthly_contribution' => 5000.0, 'current_age' => 59, 'retirement_age' => 60, 'expected_return_rate' => 10.0, 'annuity_percent' => 40.0, 'annuity_return_rate' => 6.0],
    ['monthly_contribution' => 50000.0, 'current_age' => 28, 'retirement_age' => 60, 'expected_return_rate' => 15.0, 'annuity_percent' => 40.0, 'annuity_return_rate' => 7.0],
    ['monthly_contribution' => 3000.0, 'current_age' => 22, 'retirement_age' => 60, 'expected_return_rate' => 8.0, 'annuity_percent' => 40.0, 'annuity_return_rate' => 5.5],
];

foreach ($npsCases as $idx => $nParams) {
    echo "NPS Parity Case " . ($idx + 1) . ": ";
    $phpRes = \Core\Math\NpsEngine::calculate(
        $nParams['monthly_contribution'],
        $nParams['current_age'],
        $nParams['retirement_age'],
        $nParams['expected_return_rate'],
        $nParams['annuity_percent'],
        $nParams['annuity_return_rate']
    );

    $cmd = "node tests/run_js_calc.js " . escapeshellarg(json_encode([
        'action' => 'nps',
        'monthly_contribution' => $nParams['monthly_contribution'],
        'current_age' => $nParams['current_age'],
        'retirement_age' => $nParams['retirement_age'],
        'expected_return_rate' => $nParams['expected_return_rate'],
        'annuity_percent' => $nParams['annuity_percent'],
        'annuity_return_rate' => $nParams['annuity_return_rate'],
    ]));
    $jsRes = json_decode((string)shell_exec($cmd), true);

    $fields = ['total_invested', 'total_interest', 'total_corpus', 'lump_sum_amount', 'annuity_amount', 'monthly_pension'];
    $mismatch = false;
    foreach ($fields as $f) {
        if (abs((float)$phpRes[$f] - (float)$jsRes[$f]) > 1.0) {
            echo "FAIL: Field {$f} mismatch. PHP: {$phpRes[$f]}, JS: {$jsRes[$f]}\n";
            $failed = true;
            $mismatch = true;
            break;
        }
    }
    if (!$mismatch) {
        echo "PASS\n";
    }
}

// 10. Cross-Runtime Parity: Post-Retirement Asset Allocation & Multi-Stream Drawdown Engine
// ----------------------------------------------------------------------------------------
echo "\nTesting Post-Retirement Engine Parity (PHP vs TypeScript)...\n";
$postRetCases = [
    // 1. Baseline
    ['existing_corpus' => 5000000.0, 'gratuity_lump_sum' => 1500000.0, 'nps_lump_sum' => 4000000.0, 'guaranteed_pension' => 25000.0, 'monthly_expense' => 75000.0, 'expected_return' => 8.5, 'inflation_rate' => 6.0, 'current_age' => 60, 'planning_horizon_years' => 30],
    // 2. Perpetual
    ['existing_corpus' => 20000000.0, 'gratuity_lump_sum' => 2000000.0, 'nps_lump_sum' => 5000000.0, 'guaranteed_pension' => 40000.0, 'monthly_expense' => 60000.0, 'expected_return' => 9.0, 'inflation_rate' => 5.0, 'current_age' => 58, 'planning_horizon_years' => 35],
    // 3. Zero Pension
    ['existing_corpus' => 6000000.0, 'gratuity_lump_sum' => 1000000.0, 'nps_lump_sum' => 3000000.0, 'guaranteed_pension' => 0.0, 'monthly_expense' => 70000.0, 'expected_return' => 8.0, 'inflation_rate' => 6.0, 'current_age' => 60, 'planning_horizon_years' => 25],
    // 4. Small Corpus (Early Depletion)
    ['existing_corpus' => 1500000.0, 'gratuity_lump_sum' => 500000.0, 'nps_lump_sum' => 0.0, 'guaranteed_pension' => 10000.0, 'monthly_expense' => 50000.0, 'expected_return' => 7.5, 'inflation_rate' => 6.0, 'current_age' => 62, 'planning_horizon_years' => 20],
    // 5. High Expense / HNI
    ['existing_corpus' => 30000000.0, 'gratuity_lump_sum' => 2000000.0, 'nps_lump_sum' => 8000000.0, 'guaranteed_pension' => 50000.0, 'monthly_expense' => 200000.0, 'expected_return' => 10.0, 'inflation_rate' => 6.5, 'current_age' => 55, 'planning_horizon_years' => 35],
    // 6. Zero Gratuity & Zero NPS
    ['existing_corpus' => 10000000.0, 'gratuity_lump_sum' => 0.0, 'nps_lump_sum' => 0.0, 'guaranteed_pension' => 30000.0, 'monthly_expense' => 80000.0, 'expected_return' => 8.5, 'inflation_rate' => 6.0, 'current_age' => 60, 'planning_horizon_years' => 30],
    // 7. Late Retirement (Age 70)
    ['existing_corpus' => 4000000.0, 'gratuity_lump_sum' => 1500000.0, 'nps_lump_sum' => 2500000.0, 'guaranteed_pension' => 20000.0, 'monthly_expense' => 60000.0, 'expected_return' => 7.5, 'inflation_rate' => 5.5, 'current_age' => 70, 'planning_horizon_years' => 20],
    // 8. High Inflation Stress Test
    ['existing_corpus' => 8000000.0, 'gratuity_lump_sum' => 2000000.0, 'nps_lump_sum' => 4000000.0, 'guaranteed_pension' => 25000.0, 'monthly_expense' => 90000.0, 'expected_return' => 8.0, 'inflation_rate' => 8.0, 'current_age' => 60, 'planning_horizon_years' => 30],
];

foreach ($postRetCases as $idx => $prParams) {
    echo "Post-Retirement Parity Case " . ($idx + 1) . ": ";
    $phpRes = \Core\Math\PostRetirementEngine::calculate(
        $prParams['existing_corpus'],
        $prParams['gratuity_lump_sum'],
        $prParams['nps_lump_sum'],
        $prParams['guaranteed_pension'],
        $prParams['monthly_expense'],
        $prParams['expected_return'],
        $prParams['inflation_rate'],
        $prParams['current_age'],
        $prParams['planning_horizon_years']
    );

    $cmd = "node tests/run_js_calc.js " . escapeshellarg(json_encode([
        'action' => 'post_retirement',
        'existing_corpus' => $prParams['existing_corpus'],
        'gratuity_lump_sum' => $prParams['gratuity_lump_sum'],
        'nps_lump_sum' => $prParams['nps_lump_sum'],
        'guaranteed_pension' => $prParams['guaranteed_pension'],
        'monthly_expense' => $prParams['monthly_expense'],
        'expected_return' => $prParams['expected_return'],
        'inflation_rate' => $prParams['inflation_rate'],
        'current_age' => $prParams['current_age'],
        'planning_horizon_years' => $prParams['planning_horizon_years'],
    ]));
    $jsRes = json_decode((string)shell_exec($cmd), true);

    $fields = [
        'total_initial_corpus',
        'initial_monthly_swp',
        'initial_swr_percent',
        'final_balance',
        'bucket_1_cash',
        'bucket_2_income',
        'bucket_3_growth'
    ];
    $mismatch = false;
    if ($phpRes['is_perpetual'] !== $jsRes['is_perpetual']) {
        echo "FAIL: is_perpetual mismatch. PHP: " . var_export($phpRes['is_perpetual'], true) . ", JS: " . var_export($jsRes['is_perpetual'], true) . "\n";
        $failed = true;
        $mismatch = true;
    } elseif ($phpRes['depletion_year'] !== $jsRes['depletion_year']) {
        echo "FAIL: depletion_year mismatch. PHP: " . var_export($phpRes['depletion_year'], true) . ", JS: " . var_export($jsRes['depletion_year'], true) . "\n";
        $failed = true;
        $mismatch = true;
    } elseif ($phpRes['depletion_age'] !== $jsRes['depletion_age']) {
        echo "FAIL: depletion_age mismatch. PHP: " . var_export($phpRes['depletion_age'], true) . ", JS: " . var_export($jsRes['depletion_age'], true) . "\n";
        $failed = true;
        $mismatch = true;
    } else {
        foreach ($fields as $f) {
            if (abs((float)$phpRes[$f] - (float)$jsRes[$f]) > 1.0) {
                echo "FAIL: Field {$f} mismatch. PHP: {$phpRes[$f]}, JS: {$jsRes[$f]}\n";
                $failed = true;
                $mismatch = true;
                break;
            }
        }
    }
    if (!$mismatch) {
        echo "PASS\n";
    }
}

// 11. Cross-Runtime Parity: Employees' Provident Fund (EPF & VPF) Engine
// ----------------------------------------------------------------------
echo "\nTesting EPF & VPF Engine Parity (PHP vs TypeScript)...\n";
$epfCases = [
    ['basic_salary' => 50000.0, 'current_age' => 28, 'retirement_age' => 58, 'vpf_percent' => 0.0, 'salary_hike' => 7.0, 'interest_rate' => 8.25, 'current_balance' => 0.0, 'cap_eps' => true],
    ['basic_salary' => 25000.0, 'current_age' => 24, 'retirement_age' => 58, 'vpf_percent' => 0.0, 'salary_hike' => 7.0, 'interest_rate' => 8.25, 'current_balance' => 0.0, 'cap_eps' => true],
    ['basic_salary' => 100000.0, 'current_age' => 30, 'retirement_age' => 60, 'vpf_percent' => 10.0, 'salary_hike' => 8.0, 'interest_rate' => 8.25, 'current_balance' => 500000.0, 'cap_eps' => true],
    ['basic_salary' => 75000.0, 'current_age' => 35, 'retirement_age' => 58, 'vpf_percent' => 15.0, 'salary_hike' => 5.0, 'interest_rate' => 8.5, 'current_balance' => 1200000.0, 'cap_eps' => true],
    ['basic_salary' => 50000.0, 'current_age' => 28, 'retirement_age' => 58, 'vpf_percent' => 0.0, 'salary_hike' => 7.0, 'interest_rate' => 8.25, 'current_balance' => 0.0, 'cap_eps' => false],
    ['basic_salary' => 12000.0, 'current_age' => 22, 'retirement_age' => 58, 'vpf_percent' => 0.0, 'salary_hike' => 0.0, 'interest_rate' => 8.15, 'current_balance' => 0.0, 'cap_eps' => true],
    ['basic_salary' => 150000.0, 'current_age' => 42, 'retirement_age' => 58, 'vpf_percent' => 25.0, 'salary_hike' => 6.0, 'interest_rate' => 8.25, 'current_balance' => 3500000.0, 'cap_eps' => true],
    ['basic_salary' => 40000.0, 'current_age' => 50, 'retirement_age' => 60, 'vpf_percent' => 5.0, 'salary_hike' => 4.0, 'interest_rate' => 7.5, 'current_balance' => 2000000.0, 'cap_eps' => false],
];

foreach ($epfCases as $idx => $eParams) {
    echo "EPF Parity Case " . ($idx + 1) . ": ";
    $phpRes = \Core\Math\EpfEngine::calculate(
        $eParams['basic_salary'],
        $eParams['current_age'],
        $eParams['retirement_age'],
        $eParams['vpf_percent'],
        $eParams['salary_hike'],
        $eParams['interest_rate'],
        $eParams['current_balance'],
        $eParams['cap_eps']
    );

    $cmd = "node tests/run_js_calc.js " . escapeshellarg(json_encode([
        'action' => 'epf',
        'basic_salary' => $eParams['basic_salary'],
        'current_age' => $eParams['current_age'],
        'retirement_age' => $eParams['retirement_age'],
        'vpf_percent' => $eParams['vpf_percent'],
        'salary_hike' => $eParams['salary_hike'],
        'interest_rate' => $eParams['interest_rate'],
        'current_balance' => $eParams['current_balance'],
        'cap_eps' => $eParams['cap_eps'],
    ]));
    $jsRes = json_decode((string)shell_exec($cmd), true);

    $fields = [
        'total_employee_contribution',
        'total_employer_contribution',
        'total_invested',
        'total_interest',
        'maturity_corpus',
        'total_eps_diverted',
    ];
    $mismatch = false;
    if ($phpRes['exceeds_250k_cap'] !== $jsRes['exceeds_250k_cap']) {
        echo "FAIL: exceeds_250k_cap mismatch.\n";
        $failed = true;
        $mismatch = true;
    } else {
        foreach ($fields as $f) {
            if (abs((float)$phpRes[$f] - (float)$jsRes[$f]) > 1.0) {
                echo "FAIL: Field {$f} mismatch. PHP: {$phpRes[$f]}, JS: {$jsRes[$f]}\n";
                $failed = true;
                $mismatch = true;
                break;
            }
        }
    }
    if (!$mismatch) {
        echo "PASS\n";
    }
}

echo "\n";
if ($failed) {
    echo "=== CALCULATOR ENGINE PARITY TESTS FAILED ===\n";
    exit(1);
} else {
    echo "=== ALL CALCULATOR ENGINE PARITY TESTS PASSED ===\n";
    exit(0);
}
