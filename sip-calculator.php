<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All About Systematic Investment Plans (SIPs) | SIP Calculator Guide</title>
    <meta name="description"
        content="A comprehensive guide to understanding Systematic Investment Plans (SIPs), their benefits, and how to use a SIP calculator to plan your financial future.">
    <link rel="canonical" href="https://sipswpcalculator.com/sip-calculator">
    <link rel="stylesheet" href="styles.css?v=<?= time() ?>">
    <!-- Tailwind CSS (via CDN) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                    },
                    colors: {
                        indigo: {
                            50: '#eef2ff',
                            100: '#e0e7ff',
                            500: '#6366f1',
                            600: '#4f46e5',
                            700: '#4338ca',
                            900: '#312e81',
                        }
                    }
                }
            }
        }
    </script>
</head>

<body class="bg-gray-50 text-gray-800 font-sans antialiased"
    style="background-image: var(--gradient-surface); background-attachment: fixed;">
    <?php include 'navbar.php'; ?>

    <div class="max-w-4xl mx-auto p-4 sm:p-6 lg:p-8 animate-float">

        <header class="mb-12 text-center">
            <h1 class="text-4xl font-extrabold pb-2">
                <span class="text-gradient">SIP Guide</span>
            </h1>
            <p class="text-lg text-gray-500 font-medium mt-2">Mastering Systematic Investment Plans</p>
        </header>

        <main class="glass-card p-8 sm:p-12">
            <article
                class="prose prose-lg max-w-none text-gray-600 prose-headings:text-indigo-900 prose-a:text-indigo-600 hover:prose-a:text-indigo-500">
                <h2 class="text-3xl font-bold mb-6 text-gray-800 border-b border-gray-100 pb-4">A Deep Dive into
                    Systematic Investment Plans</h2>

                <p class="lead text-xl text-gray-700 font-medium">A Systematic Investment Plan, or SIP, is a powerful
                    and popular method for investing in mutual funds.
                    It allows you to invest a fixed amount of money at regular intervals, such as monthly or quarterly,
                    rather than making a single lump-sum investment.</p>

                <div class="my-8 grid grid-cols-1 md:grid-cols-2 gap-6 not-prose">
                    <div class="p-6 bg-indigo-50/50 rounded-xl border border-indigo-100">
                        <h3 class="font-bold text-indigo-700 mb-2 flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                            </svg>
                            Dollar Cost Averaging
                        </h3>
                        <p class="text-sm text-gray-600">Buy more units when prices are low and fewer when high,
                            averaging your cost over time.</p>
                    </div>
                    <div class="p-6 bg-purple-50/50 rounded-xl border border-purple-100">
                        <h3 class="font-bold text-purple-700 mb-2 flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Power of Compounding
                        </h3>
                        <p class="text-sm text-gray-600">Reinvested returns generate their own returns. Time is your
                            greatest asset.</p>
                    </div>
                </div>

                <h3 class="flex items-center gap-2 text-gray-800">
                    <span
                        class="flex items-center justify-center w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 text-sm font-bold">3</span>
                    Disciplined Investing
                </h3>
                <p>One of the biggest challenges for investors is timing the market. SIPs remove this guesswork by
                    automating your investments, ensuring consistency regardless of market conditions.</p>

                <h3 class="flex items-center gap-2 text-gray-800 mt-8">
                    <span
                        class="flex items-center justify-center w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 text-sm font-bold">4</span>
                    Flexibility
                </h3>
                <p>Start small (e.g., ₹500/month) and step up as you grow. Pause or stop anytime. It's investing on your
                    terms.</p>

                <div
                    class="mt-12 p-8 bg-gradient-to-br from-indigo-600 to-purple-600 rounded-2xl text-white shadow-xl text-center not-prose">
                    <h2 class="text-2xl font-bold mb-4 text-white">Ready to Plan Your Future?</h2>
                    <p class="mb-8 text-indigo-100">Use our advanced calculator to model your wealth creation journey.
                    </p>
                    <a href="/"
                        class="inline-flex items-center px-8 py-3 bg-white text-indigo-600 font-bold rounded-lg shadow-lg hover:bg-gray-50 transform hover:-translate-y-1 transition-all duration-200">
                        Launch SIP Calculator
                        <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                        </svg>
                    </a>
                </div>
            </article>
        </main>

        <?php include 'footer.php'; ?>

    </div>

</body>

</html>