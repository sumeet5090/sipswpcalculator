<?php
// ── Data Only ──────────────────────────────────────────────────────────
$page_config = [
    'title' => 'SIP vs. SWP: Building and Enjoying Your Corpus Over 20 Years',
    'meta_desc' => 'What happens when your 15-year SIP turns into a 15-year SWP? See the comprehensive breakdown of how to build and seamlessly withdraw your wealth.',
];
$cta = "Transition from saving to spending seamlessly. Plan your own 20-year journey—Download your customized PDF report now.";

$page_content = '
<p class="text-xl mb-6">In the world of investing, there are two distinct phases of life: <strong>Accumulation</strong> and <strong>Distribution</strong>. While the SIP (Systematic Investment Plan) is your accumulation engine, the SWP (Systematic Withdrawal Plan) is your distribution fuel.</p>

<h2 id="red-zone" class="text-2xl font-bold mt-10 mb-4">The "Retirement Red Zone" Challenge</h2>
<p class="mb-6">The transition process is not a switch you flip overnight. The most dangerous time is the **Retirement Red Zone**—the 3 years before and after retirement. A 40% market crash at age 45 is a buying opportunity; at age 60, it\'s a disaster if you don\'t have a plan to protect your corpus from sequence-of-returns risk.</p>

<h2 id="bucket-strategy" class="text-2xl font-bold mt-10 mb-4">The 3-Bucket SWP Architecture</h2>
<p class="mb-6">To bridge the gap between SIP wealth creation and SWP income, elite planners use a **Bucket Strategy**:</p>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 my-8 not-prose">
    <div class="bg-indigo-50 p-6 rounded-xl border border-indigo-100">
        <h4 class="font-bold text-indigo-900 mb-2">Bucket 1: Liquidity</h4>
        <p class="text-sm text-indigo-800">2-3 years of expenses in cash/liquid funds. Your SWP runs from here, protecting you from market volatility.</p>
    </div>
    <div class="bg-slate-50 p-6 rounded-xl border border-slate-200">
        <h4 class="font-bold text-slate-900 mb-2">Bucket 2: Stability</h4>
        <p class="text-sm text-slate-800">~40% in hybrid or debt funds. This refills Bucket 1 and provides a buffer against equity downturns.</p>
    </div>
    <div class="bg-emerald-50 p-6 rounded-xl border border-emerald-100">
        <h4 class="font-bold text-emerald-900 mb-2">Bucket 3: Growth</h4>
        <p class="text-sm text-emerald-800">The remainder stays in pure equity. Let it compound for 10+ years to fight long-term inflation.</p>
    </div>
</div>

<h2 class="text-2xl font-bold mt-10 mb-4">Conclusion</h2>
<p class="mb-6">A truly powerful financial plan accounts for both the accumulation and the distribution. By using the bucket strategy to bridge your SIP-to-SWP handoff, you can ensure your wealth lasts as long as you do. Start your 50-year lifecycle journey with our calculator today.</p>
';

// ── Render ──────────────────────────────────────────────────────────────
require_once __DIR__ . '/../../layouts/layout.php';
