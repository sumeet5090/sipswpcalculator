<?php

declare(strict_types=1);

$fontBold = '/System/Library/Fonts/Supplemental/Arial Bold.ttf';
$fontRegular = '/System/Library/Fonts/Supplemental/Arial.ttf';

if (!file_exists($fontBold) || !file_exists($fontRegular)) {
    throw new RuntimeException("Required Arial fonts not found on system.");
}

$calculators = [
    'sip-calculator' => [
        'category' => 'MUTUAL FUND ACCUMULATION',
        'title' => 'SIP Calculator India (2026)',
        'subtitle' => 'Model monthly compounding, Rupee Cost Averaging, 2026 LTCG tax waterfall & export free PDF audit reports.',
        'tag1' => 'Step-Up Compounding',
        'tag2' => 'Section 112A Tax Rules',
        'tag3' => 'Free PDF Report',
        'accent' => [5, 150, 105], // emerald-600
    ],
    'swp-calculator' => [
        'category' => 'RETIREMENT DECUMULATION',
        'title' => 'SWP Calculator India (2026)',
        'subtitle' => 'Plan sustainable monthly retirement income with 5% annual step-up, 4% safe withdrawal rule & 3-bucket architecture.',
        'tag1' => 'Sequence Risk Defense',
        'tag2' => '3.5%-4% SWR Modeling',
        'tag3' => 'Tax Arbitrage vs FD',
        'accent' => [13, 148, 136], // teal-600
    ],
    'sip-step-up-calculator' => [
        'category' => 'WEALTH ACCELERATION',
        'title' => 'Step-Up SIP Calculator',
        'subtitle' => 'See how 5% to 15% annual salary hike top-ups double your corpus & compress your retirement timeline by 4+ years.',
        'tag1' => '5%-15% Annual Hikes',
        'tag2' => 'Beat 6% Inflation',
        'tag3' => 'Double Final Wealth',
        'accent' => [16, 185, 129], // emerald-500
    ],
    'lumpsum-calculator' => [
        'category' => 'CAPITAL COMPOUNDING',
        'title' => 'Lumpsum Calculator India',
        'subtitle' => 'Calculate returns on one-time capital investments with compounding growth, compare SIP vs Lumpsum & plan with STP.',
        'tag1' => '25-Yr Rolling Benchmark',
        'tag2' => 'STP Deployment Engine',
        'tag3' => '2026 LTCG Harvesting',
        'accent' => [79, 70, 229], // indigo-600
    ],
    'retirement-calculator' => [
        'category' => 'END-TO-END LIFECYCLE',
        'title' => 'Retirement Planner (SIP to SWP)',
        'subtitle' => 'Model your complete Indian financial lifecycle: monthly SIP wealth accumulation followed by inflation-adjusted SWP pension.',
        'tag1' => 'Full Lifecycle Engine',
        'tag2' => 'Post-Tax Net Cash Flow',
        'tag3' => '30-Year Survival Test',
        'accent' => [14, 165, 233], // sky-600
    ],
    'my-first-crore-calculator' => [
        'category' => 'MILESTONE GOAL-SEEK',
        'title' => '1 Crore SIP Calculator',
        'subtitle' => 'Calculate the exact monthly SIP required to reach ₹1 Crore across 5 to 25 year horizons with annual step-up acceleration.',
        'tag1' => 'Exact Monthly Target',
        'tag2' => 'Step-Up Time Savings',
        'tag3' => 'Real Purchasing Power',
        'accent' => [16, 185, 129], // emerald-500
    ],
    'target-corpus-calculator' => [
        'category' => 'FINANCIAL FREEDOM',
        'title' => 'Target Corpus Goal Planner',
        'subtitle' => 'Reverse-engineer required investments for any target goal (₹25L, ₹50L, ₹1Cr, ₹5Cr) with timeline and step-up adjustments.',
        'tag1' => 'Custom Target Solver',
        'tag2' => 'Inflation Drag Adjustment',
        'tag3' => 'Milestone Breakdown',
        'accent' => [99, 102, 241], // indigo-500
    ],
    'compound-interest-calculator' => [
        'category' => 'MATHEMATICAL PRECISION',
        'title' => 'Compound Interest Calculator',
        'subtitle' => 'Visualize exponential growth curves across daily, monthly, quarterly, and annual compounding frequencies with Rule of 72.',
        'tag1' => 'Multi-Frequency Compounding',
        'tag2' => 'Rule of 72 Doubling Time',
        'tag3' => 'Principal vs Interest',
        'accent' => [20, 184, 166], // teal-500
    ],
    'cagr-calculator' => [
        'category' => 'RETURN BENCHMARKING',
        'title' => 'CAGR Calculator India (2026)',
        'subtitle' => 'Calculate Compound Annual Growth Rate, absolute returns & wealth multipliers for mutual funds, direct stocks & real estate.',
        'tag1' => 'Point-to-Point CAGR',
        'tag2' => 'Absolute Return & XIRR',
        'tag3' => 'Multi-Asset Comparison',
        'accent' => [59, 130, 246], // blue-500
    ],
    'emi-calculator' => [
        'category' => 'LOAN AMORTIZATION',
        'title' => 'EMI Calculator India (2026)',
        'subtitle' => 'Calculate monthly EMI for home loans, car loans & personal loans with complete amortization schedules & prepayment analysis.',
        'tag1' => 'Reducing Balance Formula',
        'tag2' => 'Full Amortization Table',
        'tag3' => 'Prepayment Savings',
        'accent' => [14, 116, 144], // cyan-700
    ],
    'inflation-calculator' => [
        'category' => 'PURCHASING POWER',
        'title' => 'Inflation Calculator India',
        'subtitle' => 'Project future living costs, healthcare expenses & purchasing power depreciation over 5 to 30 years using Indian CPI benchmarks.',
        'tag1' => 'CPI Inflation Modeling',
        'tag2' => 'Future Living Cost Projection',
        'tag3' => 'Real vs Nominal Value',
        'accent' => [234, 88, 12], // orange-600
    ],
    'ppf-calculator' => [
        'category' => 'GOVERNMENT GUARANTEED',
        'title' => 'PPF Calculator India (2026)',
        'subtitle' => 'Calculate Public Provident Fund returns, 5th-of-the-month interest rule, Section 80C tax deduction & 5-year block extensions.',
        'tag1' => '5th-of-Month Rule',
        'tag2' => '15 to 30-Year Extensions',
        'tag3' => '100% EEE Tax Free',
        'accent' => [5, 150, 105], // emerald-600
    ],
    'fd-calculator' => [
        'category' => 'FIXED INCOME',
        'title' => 'FD Calculator India (2026)',
        'subtitle' => 'Calculate Fixed Deposit maturity, quarterly compounding, senior citizen preferential rates & Section 194A TDS deductions.',
        'tag1' => 'Quarterly Compounding',
        'tag2' => 'Senior Citizen Rates',
        'tag3' => 'Section 194A TDS Deduction',
        'accent' => [13, 148, 136], // teal-600
    ],
    'reach-1-crore-via-sip' => [
        'category' => 'MILESTONE GOAL-SEEK',
        'title' => 'How to Reach ₹1 Crore via SIP',
        'subtitle' => 'Calculate monthly investments to reach ₹1 Crore across 5, 10, 15, and 20 years with 10% annual step-up compounding & LTCG tax rules.',
        'tag1' => '5 to 20-Year Timelines',
        'tag2' => '10% Step-Up Boost',
        'tag3' => 'LTCG Tax Waterfall',
        'accent' => [5, 150, 105], // emerald-600
    ],
    'reach-5-crore-via-sip' => [
        'category' => 'FINANCIAL FREEDOM (FIRE)',
        'title' => 'How to Reach ₹5 Crore via SIP',
        'subtitle' => 'Plan early retirement and multi-decade wealth compounding: reverse-engineer exact monthly SIPs, multi-asset allocation & SWP cash flow.',
        'tag1' => 'FIRE Retirement Plan',
        'tag2' => 'Accelerated Velocity',
        'tag3' => '4% Safe SWP Drawdown',
        'accent' => [79, 70, 229], // indigo-600
    ],
    'sip-5000-per-month' => [
        'category' => 'MONTHLY SIP BLUEPRINT',
        'title' => '₹5,000 Per Month SIP Plan',
        'subtitle' => 'Model ₹5,000 monthly SIP compounding: project ₹11.6L in 10 yrs, ₹50L in 20 yrs & over ₹2.3 Crores with 10% annual step-up.',
        'tag1' => '10 to 25-Yr Projection',
        'tag2' => '10% Step-Up Miracle',
        'tag3' => '2-Fund Core Portfolio',
        'accent' => [13, 148, 136], // teal-600
    ],
    'sip-10000-per-month' => [
        'category' => 'MONTHLY SIP BLUEPRINT',
        'title' => '₹10,000 Per Month SIP Plan',
        'subtitle' => 'Plan ₹10,000 monthly SIP returns: build ₹23.2L in 10 yrs, reach ₹1 Crore in 20 yrs & accumulate over ₹4.6 Crores with step-up.',
        'tag1' => 'Gateway to 1 Crore',
        'tag2' => '3-Fund Core Portfolio',
        'tag3' => '₹2.7 Cr Step-Up Boost',
        'accent' => [16, 185, 129], // emerald-500
    ]
];

$width = 1200;
$height = 630;

foreach ($calculators as $slug => $data) {
    $im = imagecreatetruecolor($width, $height);
    
    // Enable anti-aliasing
    imagealphablending($im, true);
    imagesavealpha($im, true);

    // 1. Base Background: Clean crisp slate-50 (#F8FAFC)
    $bg = imagecolorallocate($im, 248, 250, 252);
    imagefilledrectangle($im, 0, 0, $width, $height, $bg);

    // 2. Soft pastel ambient aura glows (Light Mode ambient lighting system)
    // Top-left soft emerald glow
    $glowColor1 = imagecolorallocate($im, 209, 250, 229); // emerald-100
    imagefilledellipse($im, 100, 80, 500, 300, $glowColor1);
    
    // Top-right soft cyan glow
    $glowColor2 = imagecolorallocate($im, 224, 242, 254); // sky-100
    imagefilledellipse($im, 1100, 100, 600, 350, $glowColor2);

    // Bottom-right soft indigo glow
    $glowColor3 = imagecolorallocate($im, 238, 242, 255); // indigo-50
    imagefilledellipse($im, 950, 550, 450, 250, $glowColor3);

    // 3. Central Premium Pure Light Card (bg-white with border-slate-200)
    $cardBg = imagecolorallocate($im, 255, 255, 255);
    $cardBorder = imagecolorallocate($im, 226, 232, 240); // slate-200
    
    $cardX1 = 60;
    $cardY1 = 50;
    $cardX2 = 1140;
    $cardY2 = 580;
    
    // Draw subtle card border and white surface
    imagefilledrectangle($im, $cardX1, $cardY1, $cardX2, $cardY2, $cardBg);
    imagerectangle($im, $cardX1, $cardY1, $cardX2, $cardY2, $cardBorder);
    imagerectangle($im, $cardX1 - 1, $cardY1 - 1, $cardX2 + 1, $cardY2 + 1, $cardBorder);

    // Colors
    $slate900 = imagecolorallocate($im, 15, 23, 42);   // #0F172A (Primary text)
    $slate700 = imagecolorallocate($im, 51, 65, 85);   // #334155 (Subtitle)
    $slate500 = imagecolorallocate($im, 100, 116, 139); // #64748B (Muted)
    $slate200 = imagecolorallocate($im, 226, 232, 240);
    $slate100 = imagecolorallocate($im, 241, 245, 249);
    
    $accentColor = imagecolorallocate($im, $data['accent'][0], $data['accent'][1], $data['accent'][2]);
    $accentLight = imagecolorallocate($im, 236, 253, 245); // emerald-50

    // Top Header Inside Card: Brand Badge
    // Brand pill: SIPSWPCALCULATOR.COM
    $badgeX = 100;
    $badgeY = 90;
    imagefilledrectangle($im, $badgeX, $badgeY, $badgeX + 240, $badgeY + 36, $accentLight);
    imagerectangle($im, $badgeX, $badgeY, $badgeX + 240, $badgeY + 36, $accentColor);
    imagettftext($im, 12, 0, $badgeX + 16, $badgeY + 24, $accentColor, $fontBold, "⚡ SIPSWPCALCULATOR.COM");

    // Right Header Tag: Category
    imagettftext($im, 11, 0, 720, $badgeY + 24, $slate500, $fontBold, "FREE FINANCIAL TOOL • 2026 EDITION");

    // Divider line below header
    imageline($im, 100, 145, 1100, 145, $slate200);

    // Category Label
    imagettftext($im, 13, 0, 100, 185, $accentColor, $fontBold, strtoupper($data['category']));

    // Main Title (Huge, High Contrast Slate-900)
    imagettftext($im, 32, 0, 100, 245, $slate900, $fontBold, $data['title']);

    // Subtitle (Word-wrapped if needed)
    $words = explode(' ', $data['subtitle']);
    $line1 = '';
    $line2 = '';
    foreach ($words as $word) {
        if (strlen($line1 . ' ' . $word) < 65) {
            $line1 .= ($line1 === '' ? '' : ' ') . $word;
        } else {
            $line2 .= ($line2 === '' ? '' : ' ') . $word;
        }
    }
    imagettftext($im, 16, 0, 100, 295, $slate700, $fontRegular, $line1);
    if ($line2 !== '') {
        imagettftext($im, 16, 0, 100, 325, $slate700, $fontRegular, $line2);
    }

    // Feature Badges (3 Pill badges across bottom of content area)
    $tags = [$data['tag1'], $data['tag2'], $data['tag3']];
    $tagX = 100;
    $tagY = 380;
    
    foreach ($tags as $tag) {
        $tagBox = imagettfbbox(12, 0, $fontBold, "✓ " . $tag);
        $tagWidth = abs($tagBox[4] - $tagBox[0]) + 30;
        
        imagefilledrectangle($im, $tagX, $tagY, $tagX + $tagWidth, $tagY + 38, $slate100);
        imagerectangle($im, $tagX, $tagY, $tagX + $tagWidth, $tagY + 38, $slate200);
        imagettftext($im, 11, 0, $tagX + 15, $tagY + 25, $slate700, $fontBold, "✓ " . $tag);
        
        $tagX += $tagWidth + 18;
    }

    // Bottom Card Footer / Trust Bar
    imageline($im, 100, 470, 1100, 470, $slate200);
    
    // Trust Badges Left
    imagettftext($im, 13, 0, 100, 520, $slate500, $fontBold, "🔒 100% Private in Browser");
    imagettftext($im, 13, 0, 350, 520, $slate500, $fontBold, "⚡ Zero-Latency Feedback");
    imagettftext($im, 13, 0, 600, 520, $slate500, $fontBold, "📊 SEBI / AMFI Aligned");

    // Action CTA Pill Right
    $ctaX = 890;
    $ctaY = 495;
    imagefilledrectangle($im, $ctaX, $ctaY, $ctaX + 210, $ctaY + 44, $accentColor);
    $white = imagecolorallocate($im, 255, 255, 255);
    imagettftext($im, 12, 0, $ctaX + 25, $ctaY + 28, $white, $fontBold, "Simulate Online →");

    // Save as JPEG
    $outputPath = __DIR__ . "/../assets/og/og-{$slug}.jpg";
    imagejpeg($im, $outputPath, 92);
    
    echo "Generated: assets/og/og-{$slug}.jpg (" . filesize($outputPath) . " bytes)\n";
}

echo "\nAll 13 OpenGraph images successfully generated in assets/og/!\n";
