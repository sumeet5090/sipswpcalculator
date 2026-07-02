    <div class="mt-12 glass-card p-8">
        <h2 id="master-financial-future" class="text-3xl font-bold text-center mb-6">Why Combine a SIP with an SWP? (The Accumulation to Income Transition)</h2>
        <div class="prose prose-lg max-w-none text-gray-600">
            <p>Understanding the tools at your disposal is the first step toward effective financial planning. Our
                <strong>mutual fund SIP to SWP calculator</strong> is designed to demystify two of the most powerful tools
                for Indian investors: the Systematic Investment Plan (SIP) for accumulation and the Systematic Withdrawal Plan (SWP) for systematic distribution.
            </p>

            <!-- Standalone SIP Definition -->
            <div class="grid md:grid-cols-2 gap-8 mt-8">
                <div itemscope itemtype="https://schema.org/DefinedTerm">
                    <h3 class="text-2xl font-semibold mb-3 text-emerald-600" id="what-is-sip">What is a Systematic Investment Plan (SIP)?</h3>
                    <p itemprop="description">A <dfn><strong>Systematic Investment Plan (SIP)</strong></dfn> is a method
                        of investing a fixed amount of money at regular intervals (monthly, quarterly) into mutual funds.
                        SIPs use <strong>rupee cost averaging</strong> and <strong>compounding</strong> to build wealth
                        over time, making them ideal for long-term Indian financial goals like retirement, child education, or wealth creation.
                        SIP inflows in India have reached historic highs, with monthly contributions exceeding thousands of crores of rupees.
                        <a href="/sip-calculator" class="text-emerald-600 hover:underline font-medium">Read our complete
                            SIP guide →</a>
                    </p>
                    <ul class="mt-4 space-y-2">
                        <li><span class="font-semibold text-green-700">Rupee Cost Averaging:</span> Buy more mutual fund units when
                            prices are low, fewer when they're high — reducing your average cost automatically.</li>
                        <li><span class="font-semibold text-green-700">Power of Compounding:</span> Reinvesting returns
                            generates earnings on earnings, leading to exponential growth over 10-20+ years.</li>
                        <li><span class="font-semibold text-green-700">Disciplined Investing:</span> Automates saving
                            and removes emotional decision-making from market volatility.</li>
                    </ul>
                </div>

                <!-- Standalone SWP Definition -->
                <div itemscope itemtype="https://schema.org/DefinedTerm">
                    <h3 class="text-2xl font-semibold mb-3 text-purple-600" id="what-is-swp">What is a Systematic Withdrawal Plan (SWP)?</h3>
                    <p itemprop="description">A <dfn><strong>Systematic Withdrawal Plan (SWP)</strong></dfn> allows
                        investors to withdraw a fixed amount from their accumulated mutual fund corpus at regular intervals.
                        SWP provides a steady, <strong>tax-efficient income stream</strong> during retirement while
                        allowing the remaining investment to continue growing. Unlike FD interest (taxed at your slab rate),
                        SWP withdrawals are taxed only on the capital gains portion — making them significantly more efficient for retirees in India.
                        <a href="/#panel-swp" class="text-purple-600 hover:underline font-medium">Try the SWP calculator
                            →</a>
                    </p>
                    <ul class="mt-4 space-y-2">
                        <li><span class="font-semibold text-green-700">Regular Income:</span> Create a predictable
                            pension-like cash flow from your mutual fund investments.</li>
                        <li><span class="font-semibold text-green-700">Tax-Efficient Withdrawals:</span> Only the
                            capital gains portion is taxed, making SWP significantly more efficient than FD interest.</li>
                        <li><span class="font-semibold text-green-700">Continued Growth:</span> The remaining corpus stays
                            invested and benefits from compounding, potentially beating inflation.</li>
                    </ul>
                </div>
            </div>

            <!-- How to Use This Calculator -->
            <div class="mt-12">
                <h2 id="how-to-use-calculator" class="text-2xl font-bold text-gray-800 mb-4">How to Plan Your SIP to SWP Strategy</h2>
                <div class="grid md:grid-cols-3 gap-6">
                    <div class="bg-emerald-50/50 p-6 rounded-xl border border-emerald-100">
                        <div class="text-emerald-600 font-bold text-lg mb-2">Step 1: Enter SIP Details</div>
                        <p class="text-sm">Set your <strong>initial lumpsum</strong> and <strong>monthly SIP amount</strong> (from ₹500 to ₹10,00,000), investment
                            period, expected annual return rate, and optional <strong>annual step-up percentage</strong> to match salary growth.</p>
                    </div>
                    <div class="bg-rose-50/50 p-6 rounded-xl border border-rose-100">
                        <div class="text-rose-600 font-bold text-lg mb-2">Step 2: Configure SWP (Optional)</div>
                        <p class="text-sm">Switch to the SWP tab, enable it, and set your <strong>monthly withdrawal
                                amount</strong>, SWP period, separate retirement interest rate, and yearly hike to guard against inflation.</p>
                    </div>
                    <div class="bg-emerald-50/50 p-6 rounded-xl border border-emerald-100">
                        <div class="text-emerald-600 font-bold text-lg mb-2">Step 3: Analyze Results</div>
                        <p class="text-sm">View the interactive <strong>growth chart</strong>, yearly breakdown table,
                            and summary cards. Export results as <strong>CSV</strong> or generate a branded <strong>PDF
                                report</strong> for your planning.</p>
                    </div>
                </div>
            </div>

            <!-- SIP Formula -->
            <div class="mt-12">
                <h2 id="sip-calculator-formula" class="text-2xl font-bold text-gray-800 mb-4">SIP Calculator Formula</h2>
                <div
                    class="bg-gray-50 p-6 rounded-xl border border-gray-200 font-mono text-sm sm:text-base overflow-x-auto">
                    <p class="font-bold text-emerald-700 mb-2">Future Value of SIP (Annuity Due):</p>
                    <p class="text-lg mb-4">FV = P × [ { (1 + i)<sup>n</sup> - 1 } / i ] × (1 + i)</p>
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-sm">
                        <div>
                            <dt class="inline font-bold">FV</dt>
                            <dd class="inline">= Future Value (Maturity Amount)</dd>
                        </div>
                        <div>
                            <dt class="inline font-bold">P</dt>
                            <dd class="inline">= Monthly Investment Amount</dd>
                        </div>
                        <div>
                            <dt class="inline font-bold">i</dt>
                            <dd class="inline">= Monthly Rate (Annual Rate ÷ 12 ÷ 100)</dd>
                        </div>
                        <div>
                            <dt class="inline font-bold">n</dt>
                            <dd class="inline">= Total Payments (Years × 12)</dd>
                        </div>
                    </dl>
                </div>
                <p class="mt-4 text-sm text-gray-500">Our calculator uses month-by-month simulation with step-up
                    compounding and lumpsum starting points, which is more accurate than the simple annuity formula for long-term projections.</p>
            </div>

            <!-- Worked Examples -->
            <div class="mt-12">
                <h2 id="sip-return-examples" class="text-2xl font-bold text-gray-800 mb-6">SIP to SWP Calculation Examples (Indian Mutual Funds)</h2>
                <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6 not-prose">
                    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                        <h3 class="text-lg font-bold text-emerald-700 mb-2">₹5,000/month for 15 Years</h3>
                        <p class="text-xs text-gray-500 mb-3">@ 12% return, 10% annual step-up</p>
                        <ul class="space-y-2 text-sm text-gray-700">
                            <li class="flex justify-between"><span>Total Invested:</span> <span class="font-bold"><span
                                        class="dynamic-amount" data-amount-inr="1909000"></span></span></li>
                            <li class="flex justify-between"><span>Wealth Gained:</span> <span
                                    class="font-bold text-green-700">+<span class="dynamic-amount"
                                         data-amount-inr="2141000"></span></span></li>
                            <li class="flex justify-between border-t border-gray-100 pt-2 text-base"><span>Maturity
                                    Value:</span> <span class="font-bold text-emerald-700"><span class="dynamic-amount"
                                         data-amount-inr="4050000"></span></span></li>
                        </ul>
                        <p class="text-xs text-gray-500 mt-3">Money multiplied ~2.1×</p>
                    </div>
                    <div class="bg-white p-6 rounded-xl shadow-sm border border-emerald-100 ring-2 ring-emerald-100">
                        <div class="text-xs font-bold text-emerald-600 mb-1">MOST POPULAR</div>
                        <h3 class="text-lg font-bold text-emerald-700 mb-2">₹10,000/month for 20 Years</h3>
                        <p class="text-xs text-gray-500 mb-3">@ 12% return, 10% annual step-up</p>
                        <ul class="space-y-2 text-sm text-gray-700">
                            <li class="flex justify-between"><span>Total Invested:</span> <span class="font-bold"><span
                                        class="dynamic-amount" data-amount-inr="6873000"></span></span></li>
                            <li class="flex justify-between"><span>Wealth Gained:</span> <span
                                    class="font-bold text-green-700">+<span class="dynamic-amount"
                                         data-amount-inr="28527000"></span></span></li>
                            <li class="flex justify-between border-t border-gray-100 pt-2 text-base"><span>Maturity
                                    Value:</span> <span class="font-bold text-emerald-700"><span class="dynamic-amount"
                                         data-amount-inr="35400000"></span></span></li>
                        </ul>
                        <p class="text-xs text-gray-500 mt-3">Money multiplied ~5.1×</p>
                    </div>
                    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                        <h3 class="text-lg font-bold text-rose-700 mb-2">₹25,000/month for 30 Years</h3>
                        <p class="text-xs text-gray-500 mb-3">@ 12% return, 10% annual step-up</p>
                        <ul class="space-y-2 text-sm text-gray-700">
                            <li class="flex justify-between"><span>Total Invested:</span> <span class="font-bold"><span
                                        class="dynamic-amount" data-amount-inr="49400000"></span></span></li>
                            <li class="flex justify-between"><span>Wealth Gained:</span> <span
                                    class="font-bold text-green-700">+<span class="dynamic-amount"
                                         data-amount-inr="369100000"></span></span></li>
                            <li class="flex justify-between border-t border-gray-100 pt-2 text-base"><span>Maturity
                                    Value:</span> <span class="font-bold text-rose-700"><span class="dynamic-amount"
                                         data-amount-inr="418500000"></span></span></li>
                        </ul>
                        <p class="text-xs text-gray-500 mt-3">Money multiplied ~8.5×</p>
                    </div>
                    <div class="bg-white p-6 rounded-xl shadow-sm border border-teal-100">
                        <div class="text-xs font-bold text-teal-600 mb-1">CONSERVATIVE PLAN</div>
                        <h3 class="text-lg font-bold text-teal-700 mb-2">₹15,000/month for 20 Years</h3>
                        <p class="text-xs text-gray-500 mb-3">@ 10% return, 5% annual step-up</p>
                        <ul class="space-y-2 text-sm text-gray-700">
                            <li class="flex justify-between"><span>Total Invested:</span> <span class="font-bold"><span
                                        class="dynamic-amount" data-amount-inr="5951800"></span></span></li>
                            <li class="flex justify-between"><span>Wealth Gained:</span> <span
                                    class="font-bold text-green-700">+<span class="dynamic-amount"
                                         data-amount-inr="7684200"></span></span></li>
                            <li class="flex justify-between border-t border-gray-100 pt-2 text-base"><span>Maturity
                                    Value:</span> <span class="font-bold text-teal-700"><span class="dynamic-amount"
                                         data-amount-inr="13636000"></span></span></li>
                        </ul>
                        <p class="text-xs text-gray-500 mt-3">Money multiplied ~2.3×</p>
                    </div>
                </div>
                <p class="text-xs text-gray-500 mt-4 text-center">Note: These are illustrative projections. Actual
                    returns depend on market conditions. Mutual fund investments are subject to market risks. Read all
                    scheme-related documents carefully.</p>
            </div>

            <!-- Risks Section -->
            <div class="mt-12">
                <h2 id="investment-risks" class="text-2xl font-bold text-gray-800 mb-4">Risks of SIP & SWP Investments</h2>
                <div class="bg-amber-50/50 p-6 rounded-xl border border-amber-200">
                    <ul class="space-y-3 text-gray-700">
                        <li><strong class="text-amber-700">Market Risk:</strong> Returns depend on mutual fund market
                            performance. Equity SIPs can show negative returns in the short term (1-3 years). However,
                            over 7-10+ years, diversified equity funds have historically delivered strong positive returns in India.
                        </li>
                        <li><strong class="text-amber-700">Sequence-of-Returns Risk (SWP):</strong> If markets crash
                            early in your SWP withdrawal phase, your capital depletes faster. Stress-test your withdrawal rate
                            against potential market downturns.</li>
                        <li><strong class="text-amber-700">Inflation Risk:</strong> A 6-7% return on debt funds may not
                            beat consumer inflation (5-6%). Equity SIPs historically outpace inflation over the long term.</li>
                        <li><strong class="text-amber-700">No Guaranteed Returns:</strong> Unlike government bonds, post office savings, or bank fixed
                            deposits, mutual fund returns are market-linked and not guaranteed. Past performance does not guarantee future results. Always
                            consult a SEBI-registered financial advisor before investing.</li>
                    </ul>
                </div>
            </div>

            <!-- Real-life Case study -->
            <div class="mt-12 bg-emerald-50/50 p-8 rounded-xl border border-emerald-100/50 backdrop-blur-sm">
                <h2 id="real-life-success-story" class="text-2xl font-bold text-emerald-700 mb-4">Real-Life Case Study: Achieving Croreपति Retirement via SIP to SWP</h2>
                <p class="mb-4">Meet <strong>Amit (30)</strong>, an IT professional in Bengaluru. He decides to start a monthly SIP of **₹10,000** in a Flexi Cap Mutual Fund. He commits to a **10% annual step-up** to match his yearly salary increments.</p>
                <ul class="list-disc pl-5 space-y-2 mb-4">
                    <li><strong>Goal:</strong> Accumulate a large corpus by age 50 to plan an early retirement.</li>
                    <li><strong>Accumulation (SIP Phase):</strong> Over 20 years, his monthly contribution increases by 10% every year. By age 50, his total investment is ₹68.73 Lakhs, which compounds to a final corpus of **₹3.54 Crore** (assuming a 12% CAGR).</li>
                    <li><strong>Income Generation (SWP Phase):</strong> Amit transitions his ₹3.54 Crore corpus to a Conservative Hybrid Fund returning 8% p.a. He starts a Systematic Withdrawal Plan (SWP) of **₹1.5 Lakhs/month** to cover his living expenses, with a **6% annual hike** to beat inflation.</li>
                    <li><strong>Result:</strong> He receives regular monthly income of ₹1.5 Lakhs (increasing by 6% every year). After 15 years of retirement, he has withdrawn a total of ₹4.18 Crore, and yet his remaining corpus stands at over **₹2.8+ Crores** because the balance fund continued to compound at 8%!</li>
                </ul>
                <p class="font-semibold">Moral: It's not just about starting early — it's about increasing your
                    investment as you grow. <a href="/sip-step-up-calculator"
                        class="text-emerald-600 hover:underline">Learn more about Step-Up SIP →</a></p>
            </div>

            <div class="mt-12">
                <h2 id="investment-comparison" class="text-3xl font-bold text-center mb-6">SIP vs Recurring Deposit vs Fixed Deposit: A Comparison</h2>
                <div class="glass-card overflow-hidden">
                    <table class="min-w-full">
                        <caption class="sr-only">SIP vs Fixed Deposit: Investment Comparison for Indian Investors (2026)</caption>
                        <thead>
                            <tr class="bg-gray-50 text-gray-700 text-left">
                                <th class="py-4 px-6 font-bold border-b text-xs uppercase tracking-wider">Feature
                                </th>
                                <th
                                    class="py-4 px-6 font-bold border-b text-xs uppercase tracking-wider text-emerald-600">
                                    SIP (Equity MF)</th>
                                <th class="py-4 px-6 font-bold border-b text-xs uppercase tracking-wider">Recurring
                                    Deposit (RD)</th>
                                <th class="py-4 px-6 font-bold border-b text-xs uppercase tracking-wider">Fixed
                                    Deposit (FD)</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm">
                            <tr class="border-b hover:bg-emerald-50/30 transition-colors">
                                <td class="py-4 px-6 font-medium text-gray-900">Expected Returns</td>
                                <td class="py-4 px-6 font-bold text-green-700">10% - 15% (High)</td>
                                <td class="py-4 px-6 text-gray-600">5% - 7% (Moderate)</td>
                                <td class="py-4 px-6 text-gray-600">5% - 7% (Moderate)</td>
                            </tr>
                            <tr class="border-b hover:bg-emerald-50/30 transition-colors">
                                <td class="py-4 px-6 font-medium text-gray-900">Risk Profile</td>
                                <td class="py-4 px-6 text-rose-500 font-medium">High (Market Linked)</td>
                                <td class="py-4 px-6 text-emerald-600 font-medium">Low Risk (Bank Backed)</td>
                                <td class="py-4 px-6 text-emerald-600 font-medium">Low Risk</td>
                            </tr>
                            <tr class="border-b hover:bg-emerald-50/30 transition-colors">
                                <td class="py-4 px-6 font-medium text-gray-900">Liquidity</td>
                                <td class="py-4 px-6">High (Exit Load < 1 yr)</td>
                                <td class="py-4 px-6">Moderate (Lock-in period)</td>
                                <td class="py-4 px-6">High (Penalty applies)</td>
                            </tr>
                            <tr class="hover:bg-emerald-50/30 transition-colors">
                                <td class="py-4 px-6 font-medium text-gray-900">Taxation</td>
                                <td class="py-4 px-6">Capital Gains Tax (Equity LTCG 12.5%)</td>
                                <td class="py-4 px-6">Interest Taxed as standard Income</td>
                                <td class="py-4 px-6">Interest Taxed as standard Income</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php
            $faqRepository = new \Core\FaqRepository();
            $homeFaqs = $faqRepository->getByTag('home');
            ?>
            <!-- Curated FAQ Section -->
            <div class="mt-16 border-t border-slate-200/60 pt-12">
                <h2 id="faq-section" class="text-3xl font-bold text-center mb-8">Frequently Asked Questions (FAQ) on SIP to SWP</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <?php foreach ($homeFaqs as $index => $faq): 
                        $isLastOdd = ($index === count($homeFaqs) - 1 && count($homeFaqs) % 2 !== 0);
                        $colSpan = $isLastOdd ? 'md:col-span-2' : '';
                        $num = $index + 1;
                    ?>
                        <div class="<?= $colSpan ?>">
                            <h3 id="faq-<?= $num ?>" class="text-lg font-bold text-slate-800 mb-2"><?= $num ?>. <?= htmlspecialchars($faq['q']) ?></h3>
                            <p class="text-sm text-slate-600 leading-relaxed"><?= htmlspecialchars($faq['a']) ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <p class="text-center mt-12">Use our <a href="/#calculator-section"
                    class="text-emerald-600 hover:underline font-medium">advanced
                    SIP & SWP calculator</a> to model your investments and plan your
                withdrawals to see how you can achieve your financial goals, whether it's building a retirement
                corpus, funding your child's education, or creating a passive income stream.
                <a href="/faq" class="text-emerald-600 hover:underline font-medium">Have more questions? Check our
                    FAQ →</a>
            </p>
        </div>
        <div class="mt-12 p-6 bg-slate-50 border border-slate-200 rounded-xl text-center max-w-2xl mx-auto">
            <p class="font-bold text-slate-800 text-lg mb-2">Cite This Calculator</p>
            <p class="text-slate-600 text-sm mb-4">Writing an article, blog post, or research paper? You can cite this
                tool using the following format.</p>
            <div
                class="bg-white p-4 border border-slate-200 rounded-lg text-left text-sm text-slate-700 font-mono overflow-x-auto select-all">
                Boga, S. (2026). Advanced SIP & SWP Calculator. sipswpcalculator.com. Retrieved from
                https://sipswpcalculator.com/
            </div>
        </div>
    </div>
