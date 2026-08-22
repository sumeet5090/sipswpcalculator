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

echo "\n";
if ($failed) {
    echo "=== CALCULATOR ENGINE PARITY TESTS FAILED ===\n";
    exit(1);
} else {
    echo "=== ALL CALCULATOR ENGINE PARITY TESTS PASSED ===\n";
    exit(0);
}
