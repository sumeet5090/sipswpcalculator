<?php
// ── Data Only ──────────────────────────────────────────────────────────
$title = "Financial Planning Blog - SIP & SWP Insights";
$meta_desc = "Master your financial journey with our expert guides on SIP, SWP, the 4% rule, and wealth-building strategies for a stress-free retirement.";
$cta = "Ready to start your own 20-year journey? Use our free calculator now.";

$page_content = '
<p class="text-xl mb-12 text-slate-600">Explore our latest strategic guides designed to help you build, grow, and protect your wealth over the next two decades.</p>

<div class="grid grid-cols-1 md:grid-cols-2 gap-8 mt-12">
    <!-- Post 1 -->
    <a href="20-year-wealth-blueprint-step-up-sip.php" class="group block p-6 rounded-2xl border border-slate-100 hover:border-indigo-100 hover:bg-indigo-50/30 transition-all shadow-sm">
        <h3 class="text-xl font-bold text-slate-900 mb-3 group-hover:text-indigo-600 transition-colors">The 20-Year Wealth Blueprint</h3>
        <p class="text-slate-600 text-sm mb-4">How a simple 10% annual step-up SIP can more than double your final retirement corpus.</p>
        <span class="text-xs font-bold text-indigo-600 uppercase tracking-wider group-hover:translate-x-1 inline-flex items-center gap-1 transition-transform">Read Guide <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path></svg></span>
    </a>

    <!-- Post 2 -->
    <a href="retirement-planning-4-percent-swp-rule.php" class="group block p-6 rounded-2xl border border-slate-100 hover:border-indigo-100 hover:bg-indigo-50/30 transition-all shadow-sm">
        <h3 class="text-xl font-bold text-slate-900 mb-3 group-hover:text-indigo-600 transition-colors">The 4% SWP Rule Explained</h3>
        <p class="text-slate-600 text-sm mb-4">Learn the "Golden Rule" of retirement spending to ensure your money lasts as long as you do.</p>
        <span class="text-xs font-bold text-indigo-600 uppercase tracking-wider group-hover:translate-x-1 inline-flex items-center gap-1 transition-transform">Read Guide <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path></svg></span>
    </a>

    <!-- Post 3 -->
    <a href="sip-vs-swp-wealth-creation-withdrawal-strategy.php" class="group block p-6 rounded-2xl border border-slate-100 hover:border-indigo-100 hover:bg-indigo-50/30 transition-all shadow-sm">
        <h3 class="text-xl font-bold text-slate-900 mb-3 group-hover:text-indigo-600 transition-colors">SIP vs. SWP: Build & Enjoy</h3>
        <p class="text-slate-600 text-sm mb-4">Understanding the transition from the accumulation phase to the distribution phase of your life.</p>
        <span class="text-xs font-bold text-indigo-600 uppercase tracking-wider group-hover:translate-x-1 inline-flex items-center gap-1 transition-transform">Read Guide <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path></svg></span>
    </a>

    <!-- Post 4 -->
    <a href="reach-1-million-dollar-1-crore-rupees-in-18-years.php" class="group block p-6 rounded-2xl border border-slate-100 hover:border-indigo-100 hover:bg-indigo-50/30 transition-all shadow-sm">
        <h3 class="text-xl font-bold text-slate-900 mb-3 group-hover:text-indigo-600 transition-colors">How to Reach $1 Million / ₹1 Crore</h3>
        <p class="text-slate-600 text-sm mb-4">A practical roadmap to hitting your first million in under two decades through mutual funds.</p>
        <span class="text-xs font-bold text-indigo-600 uppercase tracking-wider group-hover:translate-x-1 inline-flex items-center gap-1 transition-transform">Read Guide <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path></svg></span>
    </a>

    <!-- Post 5 -->
    <a href="why-flat-sips-lose-money-stepup-sip-power.php" class="group block p-6 rounded-2xl border border-slate-100 hover:border-indigo-100 hover:bg-indigo-50/30 transition-all shadow-sm md:col-span-2">
        <h3 class="text-xl font-bold text-slate-900 mb-3 group-hover:text-indigo-600 transition-colors">Why Flat SIPs Are Costing You Millions</h3>
        <p class="text-slate-600 text-sm mb-4">Break free from stagnant investing. Learn how annual step-ups can massively increase your long-term returns.</p>
        <span class="text-xs font-bold text-indigo-600 uppercase tracking-wider group-hover:translate-x-1 inline-flex items-center gap-1 transition-transform">Read Guide <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path></svg></span>
    </a>
</div>
';

// ── Render ──────────────────────────────────────────────────────────────
require_once __DIR__ . '/../includes/layout.php';
