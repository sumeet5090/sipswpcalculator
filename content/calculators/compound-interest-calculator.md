# Compound Interest Calculator 2026 — Free Online CI Calculator
See exactly how your money grows with the power of compounding

---

<div class="not-prose mb-12">
    <div class="glass-card p-6 sm:p-8">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <label for="ci-principal" class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5 block">Initial Investment</label>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm font-bold text-slate-500" id="ci-symbol"><span class="currency-text">$</span></span>
                    <input type="number" id="ci-principal" value="100000" class="w-full bg-white border border-slate-200 rounded-lg pl-7 pr-3 py-3 text-sm font-bold text-indigo-600 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500/30">
                </div>
            </div>
            <div>
                <label for="ci-rate" class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5 block">Annual Interest Rate</label>
                <div class="relative">
                    <input type="number" id="ci-rate" value="8" step="0.1" min="0.1" max="50" class="w-full bg-white border border-slate-200 rounded-lg px-3 py-3 pr-8 text-sm font-bold text-slate-700 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500/30">
                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-sm font-bold text-slate-500">%</span>
                </div>
            </div>
            <div>
                <label for="ci-years" class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5 block">Time Period</label>
                <div class="relative">
                    <input type="number" id="ci-years" value="10" min="1" max="50" class="w-full bg-white border border-slate-200 rounded-lg px-3 py-3 pr-10 text-sm font-bold text-slate-700 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500/30">
                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-sm font-bold text-slate-500">Yrs</span>
                </div>
            </div>
            <div>
                <label for="ci-frequency" class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5 block">Compounding Frequency</label>
                <select id="ci-frequency" class="w-full bg-white border border-slate-200 rounded-lg px-3 py-3 text-sm font-bold text-slate-700 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500/30 cursor-pointer">
                    <option value="1">Annually</option>
                    <option value="4">Quarterly</option>
                    <option value="12" selected>Monthly</option>
                    <option value="365">Daily</option>
                </select>
            </div>
        </div>

        <div id="ci-results" class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
            <div class="bg-indigo-50 p-4 rounded-xl text-center border border-indigo-100">
                <div class="text-[10px] font-bold text-indigo-600 uppercase tracking-widest mb-1">Final Amount</div>
                <div id="ci-final" class="text-xl font-extrabold text-indigo-700 font-mono"><span class="currency-text">$</span>2,15,892</div>
            </div>
            <div class="bg-emerald-50 p-4 rounded-xl text-center border border-emerald-100">
                <div class="text-[10px] font-bold text-emerald-400 uppercase tracking-widest mb-1">Interest Earned</div>
                <div id="ci-interest" class="text-xl font-extrabold text-emerald-600 font-mono"><span class="currency-text">$</span>1,15,892</div>
            </div>
            <div class="bg-slate-50 p-4 rounded-xl text-center border border-slate-200">
                <div class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Effective Annual Rate</div>
                <div id="ci-effective" class="text-xl font-extrabold text-slate-700 font-mono">8.30%</div>
            </div>
        </div>

        <div class="bg-amber-50 p-4 rounded-xl border border-amber-200 text-center">
            <p class="text-sm text-amber-800"><strong>📏 Rule of 72:</strong> At <span id="ci-rule72-rate">8</span>% interest, your money doubles in approximately <strong id="ci-rule72-years">9</strong> years.</p>
        </div>
    </div>
</div>

<h2 id="what-is-compound-interest">What is Compound Interest?</h2>
<p><dfn><strong>Compound interest</strong></dfn> is interest calculated on both the initial principal and the accumulated interest from previous periods. Unlike <strong>simple interest</strong> (calculated only on the principal), compound interest grows exponentially over time — this is why Albert Einstein allegedly called it the <em>"eighth wonder of the world."</em></p>

<p>Whether you're saving in a bank fixed deposit, investing in mutual funds through <a href="/#calculator-section" class="text-indigo-600 hover:underline">SIPs</a>, or growing a retirement corpus, compound interest is the engine that multiplies your wealth.</p>

<h2>The Compound Interest Formula</h2>
<div class="not-prose bg-gray-50 p-6 rounded-xl border border-gray-200 font-mono text-sm sm:text-base overflow-x-auto mb-6">
    <p class="font-bold text-indigo-700 mb-2">Standard Compound Interest Formula:</p>
    <p class="text-lg mb-4">A = P × (1 + r/n)<sup>n×t</sup></p>
    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-sm">
        <div><dt class="inline font-bold">A</dt><dd class="inline">= Final amount (principal + interest)</dd></div>
        <div><dt class="inline font-bold">P</dt><dd class="inline">= Initial principal (starting amount)</dd></div>
        <div><dt class="inline font-bold">r</dt><dd class="inline">= Annual interest rate (as decimal)</dd></div>
        <div><dt class="inline font-bold">n</dt><dd class="inline">= Compounding frequency per year</dd></div>
        <div><dt class="inline font-bold">t</dt><dd class="inline">= Time period in years</dd></div>
    </dl>
</div>

<h2>Compound Interest vs. Simple Interest</h2>
<div class="not-prose overflow-x-auto mb-6">
    <table class="w-full text-sm border-collapse">
        <thead>
            <tr class="bg-slate-50">
                <th class="px-4 py-3 text-left border border-slate-200 font-bold">Feature</th>
                <th class="px-4 py-3 text-left border border-slate-200 font-bold text-emerald-700">Compound Interest</th>
                <th class="px-4 py-3 text-left border border-slate-200 font-bold text-slate-600">Simple Interest</th>
            </tr>
        </thead>
        <tbody>
            <tr><td class="px-4 py-3 border border-slate-200 font-medium">Interest on</td><td class="px-4 py-3 border border-slate-200 text-emerald-700">Principal + accumulated interest</td><td class="px-4 py-3 border border-slate-200">Principal only</td></tr>
            <tr class="bg-slate-50/50"><td class="px-4 py-3 border border-slate-200 font-medium">Growth pattern</td><td class="px-4 py-3 border border-slate-200 text-emerald-700">Exponential (accelerating)</td><td class="px-4 py-3 border border-slate-200">Linear (constant)</td></tr>
            <tr><td class="px-4 py-3 border border-slate-200 font-medium"><span class="dynamic-amount" data-amount-inr="1000000"></span> at 10% for 20 years</td><td class="px-4 py-3 border border-slate-200 text-emerald-700 font-bold"><span class="dynamic-amount" data-amount-inr="672750"></span></td><td class="px-4 py-3 border border-slate-200"><span class="dynamic-amount" data-amount-inr="300000"></span></td></tr>
            <tr class="bg-slate-50/50"><td class="px-4 py-3 border border-slate-200 font-medium"><span class="dynamic-amount" data-amount-inr="100000"></span> at 8% for 30 years</td><td class="px-4 py-3 border border-slate-200 text-emerald-700 font-bold"><span class="dynamic-amount" data-amount-inr="1006270"></span></td><td class="px-4 py-3 border border-slate-200"><span class="dynamic-amount" data-amount-inr="340000"></span></td></tr>
            <tr><td class="px-4 py-3 border border-slate-200 font-medium">Best for</td><td class="px-4 py-3 border border-slate-200 text-emerald-700">Long-term investments, SIPs, mutual funds</td><td class="px-4 py-3 border border-slate-200">Short-term loans, car loans</td></tr>
        </tbody>
    </table>
</div>

<h2>How Compounding Frequency Affects Your Returns</h2>
<p>The more frequently interest is compounded, the more you earn. Here's a comparison for a <span class="dynamic-amount" data-amount-inr="100000"></span> investment at 10% for 10 years:</p>
<div class="not-prose overflow-x-auto mb-6">
    <table class="w-full text-sm border-collapse">
        <thead>
            <tr class="bg-slate-50">
                <th class="px-4 py-3 text-left border border-slate-200 font-bold">Frequency</th>
                <th class="px-4 py-3 text-right border border-slate-200 font-bold">Final Amount</th>
                <th class="px-4 py-3 text-right border border-slate-200 font-bold">Interest Earned</th>
            </tr>
        </thead>
        <tbody>
            <tr><td class="px-4 py-3 border border-slate-200">Annual (n=1)</td><td class="px-4 py-3 border border-slate-200 text-right font-mono"><span class="dynamic-amount" data-amount-inr="259374"></span></td><td class="px-4 py-3 border border-slate-200 text-right font-mono text-emerald-600"><span class="dynamic-amount" data-amount-inr="159374"></span></td></tr>
            <tr class="bg-slate-50/50"><td class="px-4 py-3 border border-slate-200">Quarterly (n=4)</td><td class="px-4 py-3 border border-slate-200 text-right font-mono"><span class="dynamic-amount" data-amount-inr="268506"></span></td><td class="px-4 py-3 border border-slate-200 text-right font-mono text-emerald-600"><span class="dynamic-amount" data-amount-inr="168506"></span></td></tr>
            <tr><td class="px-4 py-3 border border-slate-200 font-medium text-indigo-700">Monthly (n=12)</td><td class="px-4 py-3 border border-slate-200 text-right font-mono font-bold text-indigo-700"><span class="dynamic-amount" data-amount-inr="270704"></span></td><td class="px-4 py-3 border border-slate-200 text-right font-mono text-emerald-600 font-bold"><span class="dynamic-amount" data-amount-inr="170704"></span></td></tr>
            <tr class="bg-slate-50/50"><td class="px-4 py-3 border border-slate-200">Daily (n=365)</td><td class="px-4 py-3 border border-slate-200 text-right font-mono"><span class="dynamic-amount" data-amount-inr="271791"></span></td><td class="px-4 py-3 border border-slate-200 text-right font-mono text-emerald-600"><span class="dynamic-amount" data-amount-inr="171791"></span></td></tr>
        </tbody>
    </table>
</div>

<h2>The Rule of 72: A Quick Mental Math Trick</h2>
<p>The <strong>Rule of 72</strong> is the fastest way to estimate how long it takes to double your money. Simply divide 72 by the annual interest rate:</p>
<div class="not-prose bg-indigo-50/50 p-6 rounded-xl border border-indigo-100 mb-6 text-center">
    <p class="text-lg font-bold text-indigo-700 mb-3">Years to double ≈ 72 ÷ Interest Rate</p>
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-sm">
        <div class="bg-white p-3 rounded-lg shadow-sm"><div class="font-bold text-indigo-600">6%</div><div class="text-gray-600">~12 years</div></div>
        <div class="bg-white p-3 rounded-lg shadow-sm"><div class="font-bold text-indigo-600">8%</div><div class="text-gray-600">~9 years</div></div>
        <div class="bg-white p-3 rounded-lg shadow-sm"><div class="font-bold text-indigo-600">10%</div><div class="text-gray-600">~7.2 years</div></div>
        <div class="bg-white p-3 rounded-lg shadow-sm"><div class="font-bold text-indigo-600">12%</div><div class="text-gray-600">~6 years</div></div>
    </div>
</div>

<h2>Compound Interest in Real-World Investments</h2>
<p>Compound interest powers virtually every investment vehicle:</p>
<ul>
    <li><strong>Mutual Fund SIPs:</strong> Monthly investments compound over time. Use our <a href="/#calculator-section" class="text-indigo-600 hover:underline">SIP Calculator</a> to see the effect of step-up compounding.</li>
    <li><strong>Fixed Deposits (FDs):</strong> Banks compound quarterly. An 8% FD actually yields 8.24% effective annual return.</li>
    <li><strong>PPF (Public Provident Fund):</strong> Compounds annually at government-set rates (currently 7.1% worldwide).</li>
    <li><strong>Stock Market:</strong> Reinvested dividends compound over decades, which is why long-term equity investors dramatically outperform short-term traders.</li>
</ul>

<div class="mt-12 bg-indigo-50/50 p-6 rounded-xl border border-indigo-100 not-prose">
    <h3 class="text-xl font-bold text-gray-800 mb-4">Related Guides</h3>
    <ul class="space-y-2 text-sm">
        <li><a href="/sip-calculator" class="text-indigo-600 hover:underline font-medium">SIP Guide 2026</a> — Formula, Tax Rules & Strategy</li>
        <li><a href="/sip-step-up-calculator" class="text-indigo-600 hover:underline font-medium">Step-Up SIP</a> — How a 10% Annual Increase Doubles Your Corpus</li>
        <li><a href="/sip-vs-fd-vs-ppf" class="text-indigo-600 hover:underline font-medium">SIP vs FD vs PPF</a> — Which is Best?</li>
        <li><a href="/swp-retirement-planning" class="text-indigo-600 hover:underline font-medium">SWP Retirement Planning</a> — Convert your corpus into income</li>
    </ul>
</div>

<div class="mt-12 p-8 bg-gradient-to-br from-indigo-600 to-purple-600 rounded-2xl text-white shadow-xl text-center not-prose">
    <h2 class="text-2xl font-bold mb-4 text-white">Ready to Plan Your SIP?</h2>
    <p class="mb-8 text-indigo-100">Compound interest is even more powerful when combined with regular monthly investments (SIPs). Our free calculator models step-up SIP with built-in SWP retirement planning.</p>
    <a href="/#calculator-section" class="inline-flex items-center px-8 py-3 bg-white text-indigo-600 font-bold rounded-lg shadow-lg hover:bg-gray-50 transform hover:-translate-y-1 transition-all duration-200">
        Launch SIP Calculator
        <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
        </svg>
    </a>
</div>
