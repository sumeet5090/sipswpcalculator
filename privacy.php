<?php
declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy | SIP & SWP Calculator</title>
    <meta name="description"
        content="Privacy policy for the SIP & SWP Calculator. We respect your privacy and do not collect personal financial data.">
    <link rel="canonical" href="https://sipswpcalculator.com/privacy">
    <meta name="robots" content="noindex, follow">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://sipswpcalculator.com/privacy">
    <meta property="og:title" content="Privacy Policy | SIP & SWP Calculator">
    <meta property="og:description"
        content="Privacy policy for the SIP & SWP Calculator. We respect your privacy and do not collect personal financial data.">
    <meta property="og:image" content="https://sipswpcalculator.com/assets/og-image-main.jpg">
    <meta name="twitter:card" content="summary_large_image">
    <link rel="stylesheet" href="styles.css?v=<?= filemtime(__DIR__ . '/styles.css') ?>">
    <link rel="stylesheet" href="dist/tailwind.min.css?v=<?= filemtime(__DIR__ . '/dist/tailwind.min.css') ?>">
    <script src="https://analytics.ahrefs.com/analytics.js" data-key="WiDGDiqV9F0xelXDCYFUfw" async></script>
</head>

<body class="bg-gray-50 text-gray-800 font-sans antialiased"
    style="background-image: var(--gradient-surface); background-attachment: fixed;">
    <?php include 'navbar.php'; ?>

    <div class="max-w-4xl mx-auto p-4 sm:p-6 lg:p-8">
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

        <main class="glass-card p-8 sm:p-12">
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
                    <a href="/" class="text-indigo-600 hover:underline font-medium">â† Return to SIP & SWP Calculator</a>
                </div>
            </article>
        </main>

        <?php include 'footer.php'; ?>
    </div>
</body>

</html>