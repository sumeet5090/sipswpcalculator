<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../functions.php';

$page_config = [
    'title' => 'Privacy Policy | SIP & SWP Calculator',
    'meta_desc' => 'Privacy policy for the SIP & SWP Calculator. We respect your privacy and do not collect personal financial data.',
    'canonical' => 'https://sipswpcalculator.com/privacy',
];

$schemaHelper = new \Core\SchemaHelper();
$breadcrumbs = $schemaHelper->getBreadcrumbs([
    'Home' => '/',
    'Privacy Policy' => '/privacy'
]);
$page_config['additional_head'] = '
    <meta name="robots" content="noindex, follow">
    <script type="application/ld+json">' . $breadcrumbs . '</script>
';
$active_page = 'privacy.php';

ob_start();
?>

<header class="relative mb-6 sm:mb-10 text-center">
    <div
        class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-slate-100 border border-slate-200 mb-4">
        <span class="text-sm font-semibold text-slate-600 tracking-wide uppercase">Legal Document</span>
    </div>

    <h1 class="text-3xl sm:text-5xl md:text-7xl font-extrabold pb-3 tracking-tight">
        <span class="text-gradient">Privacy Policy</span>
    </h1>

    <p class="text-base sm:text-lg text-gray-500 max-w-2xl mx-auto leading-relaxed font-medium mb-4">
        Last Updated: March 2026
    </p>
</header>

<main class="glass-card p-8 sm:p-12 max-w-4xl mx-auto">
    <article
        class="prose prose-lg max-w-none text-gray-600 prose-headings:text-indigo-900 prose-a:text-indigo-600 hover:prose-a:text-indigo-500 prose-strong:text-gray-800">
        <h2>1. Information We Collect</h2>
        <p>We believe in absolute privacy. The SIP & SWP Calculator functions entirely client-side (in your
            browser) or via stateless server calculations. <strong>We do not ask for, collect, store, or
                transmit your personal or financial data.</strong></p>
        <p>The numbers you enter into the calculator remain on your device.</p>

        <h2>2. Cookies and Tracking</h2>
        <p>We do not use tracking cookies, analytics scripts, or advertising trackers that monitor your behavior
            across the internet.</p>

        <h2>3. PDF Generation</h2>
        <p>If you choose to generate a PDF report, the numerical inputs are sent securely to our server strictly
            for the purpose of generating the PDF document. This data is processed in memory and immediately
            discarded. It is never saved to a database or logged.</p>

        <h2>4. Third-Party Links</h2>
        <p>Our website may contain links to external educational resources (such as AMFI or SEBI). Once you
            leave our site, our privacy policy no longer applies. We encourage you to read the privacy
            statements of any other site that collects personally identifiable information.</p>

        <h2>5. Contact Us</h2>
        <p>If you have any questions about this Privacy Policy, please contact us at <a
                href="mailto:help@sipswpcalculator.com"
                class="text-indigo-600 hover:underline">help@sipswpcalculator.com</a>.</p>

        <div class="mt-8 pt-6 border-t border-gray-200 text-center">
            <a href="/#calculator-section" class="text-indigo-600 hover:underline font-medium">← Return to SIP & SWP Calculator</a>
        </div>
    </article>
</main>

<?php
$page_content = ob_get_clean();
require_once __DIR__ . '/../layouts/layout.php';
?>