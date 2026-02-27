<?php declare(strict_types=1);
require_once __DIR__ . '/functions.php'; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SWP Tax Calculator India: Calculate Post-Tax Withdrawal Income</title>
    <meta name="description"
        content="Calculate the tax on your SWP withdrawals from mutual funds. Understand LTCG vs STCG, FIFO method, and how to maximize post-tax retirement income from SWP.">
    <meta name="keywords"
        content="SWP tax calculator, SWP tax India, post-tax SWP income, capital gains SWP withdrawal, mutual fund withdrawal tax">
    <link rel="canonical" href="https://sipswpcalculator.com/swp-tax-calculator">
    <meta name="robots" content="index, follow">
    <meta property="og:type" content="article">
    <meta property="og:url" content="https://sipswpcalculator.com/swp-tax-calculator">
    <meta property="og:title" content="SWP Tax Calculator: Calculate Post-Tax Withdrawal Income">
    <meta property="og:description"
        content="Calculate the tax on SWP withdrawals. LTCG vs STCG, FIFO method, and strategies to maximize post-tax retirement income.">
    <meta property="og:image" content="https://sipswpcalculator.com/assets/og-image-main.jpg">
    <meta name="twitter:card" content="summary_large_image">
    <link rel="stylesheet" href="styles.css?v=<?= filemtime(__DIR__ . '/styles.css') ?>">
    <link rel="stylesheet" href="dist/tailwind.min.css?v=<?= filemtime(__DIR__ . '/dist/tailwind.min.css') ?>">
    <link rel="icon" type="image/png" href="/assets/favicon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <script type="application/ld+json">
    {"@context":"https://schema.org","@type":"Article","headline":"SWP Tax Calculator India","author":{"@type":"Person","name":"Sumeet Boga","url":"https://sipswpcalculator.com/about"},"datePublished":"2026-02-25","dateModified":"2026-02-25"}
    </script>
    <script type="application/ld+json">
    {"@context":"https://schema.org","@type":"BreadcrumbList","itemListElement":[{"@type":"ListItem","position":1,"name":"Home","item":"https://sipswpcalculator.com/"},{"@type":"ListItem","position":2,"name":"SWP Tax Calculator","item":"https://sipswpcalculator.com/swp-tax-calculator"}]}
    </script>
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "FAQPage",
      "mainEntity": [{
        "@type": "Question",
        "name": "Is SWP from mutual funds taxable?",
        "acceptedAnswer": {
          "@type": "Answer",
          "text": "Yes, but only the capital gains portion is taxable, not the full withdrawal. The principal component is returned tax-free. This makes the effective tax rate on SWP much lower than on FD interest."
        }
      }, {
        "@type": "Question",
        "name": "How is the capital gains portion calculated?",
        "acceptedAnswer": {
          "@type": "Answer",
          "text": "Using the FIFO method: the earliest units are redeemed first. For each unit, capital gain = Redemption NAV - Purchase NAV. Your AMC provides this in the Capital Gains Statement."
        }
      }, {
        "@type": "Question",
        "name": "Can I claim tax-loss harvesting with SWP?",
        "acceptedAnswer": {
          "@type": "Answer",
          "text": "Yes. If some of your mutual fund units are at a loss, you can redeem them to book short-term capital losses, which can be set off against capital gains from SWP withdrawals. This can significantly reduce your overall tax liability."
        }
      }, {
        "@type": "Question",
        "name": "Is there GST on mutual fund transactions?",
        "acceptedAnswer": {
          "@type": "Answer",
          "text": "There's no GST on the investment or redemption amount. However, the fund's expense ratio includes GST on management fees (typically 0.5-2% annually). This is deducted from the fund's NAV, not billed separately."
        }
      }]
    }
    </script>
</head>

<body class="bg-gray-50 text-gray-800 font-sans antialiased"
    style="background-image: var(--gradient-surface); background-attachment: fixed;">
    <?php include 'navbar.php'; ?>
    <div class="max-w-4xl mx-auto p-4 sm:p-6 lg:p-8">
        <header class="mb-12 text-center">
            <h1 class="text-4xl font-extrabold pb-2"><span class="text-gradient">SWP Tax Calculator India</span></h1>
            <p class="text-lg text-gray-500 font-medium mt-2 mb-6">Understand how your SWP withdrawals are taxed and
                maximize post-tax income</p>
            <div
                class="flex items-center justify-center gap-4 text-sm text-slate-500 mb-8 pb-6 border-b border-slate-200/60">
                <span>By <strong class="text-slate-700">Sumeet Boga</strong></span><span>·</span>
                <span>Updated: <time datetime="2026-02-25">February 2026</time></span>
            </div>
        </header>
        <main class="glass-card p-8 sm:p-12">
            <article class="prose prose-lg max-w-none text-gray-600 prose-headings:text-indigo-900">

                <h2>How SWP Withdrawals Are Taxed (FIFO Method)</h2>
                <p>When you set up a Systematic Withdrawal Plan (SWP) from a mutual fund, each withdrawal is treated as
                    a <strong>partial redemption</strong>. The Income Tax Department uses the <strong>First-In-First-Out
                        (FIFO)</strong> method to determine which units are being sold.</p>
                <p>Crucially, <strong>only the capital gains portion</strong> of each withdrawal is taxable — the
                    principal (cost of acquisition) is returned tax-free. This makes SWP fundamentally different from FD
                    interest, where the entire interest amount is taxable.</p>

                <div class="bg-indigo-50 p-6 rounded-xl border border-indigo-100 not-prose mt-4">
                    <p class="font-bold text-indigo-800 mb-2">Example: ₹50,000 monthly SWP withdrawal</p>
                    <p class="text-sm text-gray-600">If you invested ₹10 Lakh and your corpus is now ₹15 Lakh:</p>
                    <ul class="text-sm text-gray-600 mt-2 space-y-1">
                        <li>• Capital gains ratio = (15L - 10L) / 15L = <strong>33.3%</strong></li>
                        <li>• Taxable portion per withdrawal = ₹50,000 × 33.3% = <strong>₹16,650</strong></li>
                        <li>• Tax-free portion (principal) = ₹50,000 × 66.7% = <strong>₹33,350</strong></li>
                        <li>• If LTCG applies: Tax = ₹16,650 × 12.5% = <strong>₹2,081/month</strong></li>
                        <li>• <strong>Effective tax rate on ₹50,000 withdrawal = only 4.2%!</strong></li>
                    </ul>
                </div>

                <h2>SWP from Equity vs Debt Funds</h2>
                <div class="overflow-x-auto not-prose">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 text-gray-700 text-left">
                                <th class="py-3 px-4 font-bold border-b">Parameter</th>
                                <th class="py-3 px-4 font-bold border-b text-indigo-600">Equity Fund SWP</th>
                                <th class="py-3 px-4 font-bold border-b">Debt Fund SWP</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="border-b">
                                <td class="py-3 px-4 font-medium">LTCG qualification</td>
                                <td class="py-3 px-4">After 1 year holding</td>
                                <td class="py-3 px-4">N/A (post Apr 2023)</td>
                            </tr>
                            <tr class="border-b">
                                <td class="py-3 px-4 font-medium">Tax rate on gains</td>
                                <td class="py-3 px-4 text-green-600">12.5% LTCG / 20% STCG</td>
                                <td class="py-3 px-4 text-rose-500">Income slab rate (always)</td>
                            </tr>
                            <tr class="border-b">
                                <td class="py-3 px-4 font-medium">Annual exemption</td>
                                <td class="py-3 px-4 text-green-600">₹1.25 Lakh LTCG</td>
                                <td class="py-3 px-4">None</td>
                            </tr>
                            <tr class="border-b">
                                <td class="py-3 px-4 font-medium">TDS</td>
                                <td class="py-3 px-4 text-green-600">No TDS</td>
                                <td class="py-3 px-4 text-green-600">No TDS</td>
                            </tr>
                            <tr>
                                <td class="py-3 px-4 font-medium">Best for</td>
                                <td class="py-3 px-4 text-indigo-600 font-medium">Long-term retirement income</td>
                                <td class="py-3 px-4">Short-term regular income</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <h2>Worked Example: Monthly ₹50,000 SWP Tax Calculation</h2>
                <p>Assume: ₹1 Crore corpus in equity hybrid fund, held >1 year, 12% annual returns, ₹50,000/month SWP:
                </p>
                <div class="overflow-x-auto not-prose">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 text-gray-700 text-left">
                                <th class="py-3 px-4 font-bold border-b">Year</th>
                                <th class="py-3 px-4 font-bold border-b">Withdrawn</th>
                                <th class="py-3 px-4 font-bold border-b">Capital Gains Portion</th>
                                <th class="py-3 px-4 font-bold border-b">Tax (12.5% LTCG)*</th>
                                <th class="py-3 px-4 font-bold border-b text-emerald-600">Post-Tax Income</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="border-b">
                                <td class="py-3 px-4">Year 1</td>
                                <td class="py-3 px-4">₹6.00L</td>
                                <td class="py-3 px-4">~₹1.80L</td>
                                <td class="py-3 px-4">₹6,875</td>
                                <td class="py-3 px-4 text-emerald-600 font-bold">₹5.93L</td>
                            </tr>
                            <tr class="border-b">
                                <td class="py-3 px-4">Year 5</td>
                                <td class="py-3 px-4">₹6.00L</td>
                                <td class="py-3 px-4">~₹2.40L</td>
                                <td class="py-3 px-4">₹14,375</td>
                                <td class="py-3 px-4 text-emerald-600 font-bold">₹5.86L</td>
                            </tr>
                            <tr>
                                <td class="py-3 px-4">Year 10</td>
                                <td class="py-3 px-4">₹6.00L</td>
                                <td class="py-3 px-4">~₹3.20L</td>
                                <td class="py-3 px-4">₹24,375</td>
                                <td class="py-3 px-4 text-emerald-600 font-bold">₹5.76L</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p class="text-xs text-gray-400">* After ₹1.25L annual LTCG exemption. Effective tax rate ranges from
                    ~1.1% to ~4.1% on total withdrawal — far lower than FD taxation.</p>

                <h2>Strategies to Minimize SWP Tax</h2>
                <ol>
                    <li><strong>Wait for LTCG:</strong> Ensure all SIP units have crossed the 1-year mark before
                        starting SWP. LTCG (12.5%) is much lower than STCG (20%).</li>
                    <li><strong>Harvest the ₹1.25L exemption:</strong> If your annual SWP gains are under ₹1.25 Lakh,
                        you pay <strong>zero tax</strong> on equity fund withdrawals.</li>
                    <li><strong>Split between spouses:</strong> If your spouse also has investments, split SWP across
                        two accounts to double the ₹1.25L exemption.</li>
                    <li><strong>Use hybrid funds:</strong> Equity-oriented hybrid funds (>65% equity) get the same tax
                        treatment as pure equity funds but with lower volatility.</li>
                    <li><strong>Strategic rebalancing:</strong> Periodically book gains within the exempt limit to
                        "reset" your cost basis higher, reducing future tax liability.</li>
                </ol>

                <div class="bg-emerald-50 p-6 rounded-xl border border-emerald-200 not-prose mt-8">
                    <p class="text-lg font-bold text-emerald-800 mb-2">Plan Your Tax-Efficient SWP</p>
                    <p class="text-gray-600 mb-4">Use our free SWP calculator to determine your optimal withdrawal
                        amount and see how long your corpus will last at different withdrawal rates.</p>
                    <a href="/"
                        class="inline-block bg-emerald-600 text-white font-bold py-3 px-6 rounded-xl hover:bg-emerald-700 transition-colors">Launch
                        SWP Calculator →</a>
                </div>

                <div class="bg-indigo-50/50 p-6 rounded-xl border border-indigo-100 mt-8 not-prose">
                    <p class="font-bold text-gray-800 mb-3">Related Reading</p>
                    <ul class="space-y-2 text-sm">
                        <li><a href="/swp-retirement-planning" class="text-indigo-600 hover:underline font-medium">SWP
                                Retirement Planning</a> — Plan your complete retirement income strategy</li>
                        <li><a href="/mutual-fund-tax-2026" class="text-indigo-600 hover:underline font-medium">Mutual
                                Fund Tax 2026</a> — Complete tax rules for all fund types</li>
                        <li><a href="/sip-vs-fd-vs-ppf" class="text-indigo-600 hover:underline font-medium">SIP vs FD vs
                                PPF</a> — Why SWP is more tax-efficient than FD interest</li>
                    </ul>
                </div>

                <h2>Frequently Asked Questions</h2>
                <details class="group">
                    <summary class="cursor-pointer font-bold text-slate-800 py-2">Is SWP from mutual funds taxable?
                    </summary>
                    <div class="pb-4 text-gray-600">Yes, but only the <strong>capital gains portion</strong> is taxable,
                        not the full withdrawal. The principal component is returned tax-free. This makes the effective
                        tax rate on SWP much lower than on FD interest.</div>
                </details>

                <details class="group">
                    <summary class="cursor-pointer font-bold text-slate-800 py-2">How is the capital gains portion
                        calculated?</summary>
                    <div class="pb-4 text-gray-600">Using the FIFO method: the earliest units are redeemed first. For
                        each unit, capital gain = Redemption NAV - Purchase NAV. Your AMC provides this in the Capital
                        Gains Statement (available on their website or via CAMS/KFintech).</div>
                </details>

                <details class="group">
                    <summary class="cursor-pointer font-bold text-slate-800 py-2">Can I claim tax-loss harvesting with
                        SWP?</summary>
                    <div class="pb-4 text-gray-600">Yes. If some of your mutual fund units are at a loss, you can redeem
                        them to book short-term capital losses, which can be set off against capital gains from SWP
                        withdrawals. This can significantly reduce your overall tax liability.</div>
                </details>

                <details class="group">
                    <summary class="cursor-pointer font-bold text-slate-800 py-2">Is there GST on mutual fund
                        transactions?</summary>
                    <div class="pb-4 text-gray-600">There's no GST on the investment or redemption amount. However, the
                        fund's expense ratio includes GST on management fees (typically 0.5-2% annually). This is
                        deducted from the fund's NAV, not billed separately.</div>
                </details>
            </article>
        </main>
        <?php include 'footer.php'; ?>
    </div>
</body>

</html>