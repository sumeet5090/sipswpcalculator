<?php

/**
 * Calculator Defaults — Single Source of Truth
 *
 * This is the authoritative configuration for all calculator input fields.
 * It defines defaults, validation bounds, and slider ranges.
 *
 * ╔══════════════════════════════════════════════════════════════════════╗
 * ║  IMPORTANT: Do NOT hardcode any of these values elsewhere.          ║
 * ║  - PHP backend:  InvestmentInputs.php reads 'min', 'max', 'default' ║
 * ║  - Twig views:   Field templates read all keys for HTML attributes   ║
 * ║  - JavaScript:   InputValidator.js reads via data-config attribute   ║
 * ╚══════════════════════════════════════════════════════════════════════╝
 *
 * Defaults are tuned for the Indian retail mutual fund investor (2026):
 *   - SIP ₹10,000/mo at 12% is the aspirational Nifty 50 benchmark
 *   - SWP ₹25,000/mo reflects urban India retirement monthly expense
 *   - 5% SWP step-up aligns with RBI's medium-term inflation target (4-6%)
 *   - 8% SWP rate reflects conservative debt/hybrid fund returns
 */

declare(strict_types=1);

return [
    // ── SIP Phase ─────────────────────────────────────────────────────
    'sip' => [
        'default'    => 10000,
        'min'        => 500,
        'max'        => 1000000,
        'slider_max' => 100000,
        'step'       => 500,
        'label'      => 'Monthly SIP',
        'prefix'     => '₹',
    ],

    'years' => [
        'default'    => 20,
        'min'        => 1,
        'max'        => 50,
        'slider_max' => 40,
        'step'       => 1,
        'label'      => 'Period (Yrs)',
        'suffix'     => 'Yrs',
    ],

    'rate' => [
        'default'    => 12,
        'min'        => 1,
        'max'        => 30,
        'slider_max' => 25,
        'step'       => 1,
        'label'      => 'Expected Return',
        'suffix'     => '%',
    ],

    'stepup' => [
        'default'    => 10,
        'min'        => 0,
        'max'        => 50,
        'slider_max' => 25,
        'step'       => 1,
        'label'      => 'Annual Step-up',
        'suffix'     => '%',
    ],

    'lumpsum' => [
        'default'    => 0,
        'min'        => 0,
        'max'        => 10000000,
        'slider_max' => 100000,
        'step'       => 5000,
        'label'      => 'Initial Lumpsum (Optional)',
        'prefix'     => '₹',
    ],

    // ── SWP Phase ─────────────────────────────────────────────────────
    'swp_withdrawal' => [
        'default'    => 25000,
        'min'        => 0,
        'max'        => 1000000,
        'slider_max' => 200000,
        'step'       => 500,
        'label'      => 'Monthly SWP',
        'prefix'     => '₹',
    ],

    'swp_years' => [
        'default'    => 20,
        'min'        => 1,
        'max'        => 50,
        'slider_max' => 40,
        'step'       => 1,
        'label'      => 'SWP Period',
        'suffix'     => 'Yrs',
    ],

    'swp_stepup' => [
        'default'    => 5,
        'min'        => 0,
        'max'        => 20,
        'slider_max' => 20,
        'step'       => 0.5,
        'label'      => 'Yearly Hike',
        'suffix'     => '%',
    ],

    'swp_rate' => [
        'default'    => 8,
        'min'        => 1,
        'max'        => 30,
        'slider_max' => 25,
        'step'       => 1,
        'label'      => 'SWP Expected Return',
        'suffix'     => '%',
    ],

    // ── SWP Corpus (Starting Balance — SWP-only calculator) ───────────
    // Semantically distinct from 'lumpsum':
    //   lumpsum = optional one-time investment alongside SIP (accumulation phase)
    //   corpus  = total accumulated wealth to start SWP withdrawals from (withdrawal phase)
    'corpus' => [
        'default'    => 5000000,
        'min'        => 10000,
        'max'        => 100000000,
        'slider_max' => 10000000,
        'step'       => 50000,
        'label'      => 'Starting Corpus',
        'prefix'     => '₹',
    ],
];
