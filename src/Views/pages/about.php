<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../functions.php'; ?>
<?php
$page_config = [
    'title' => 'About Me & Project Journey | SIP & SWP Calculator',
    'meta_desc' => 'Discover the story behind SIP & SWP Calculator — a premium, ad-free step-up compounding & retirement planning tool engineered by Sumeet Boga.',
];

ob_start();
?>
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Person",
  "name": "Sumeet Boga",
  "url": "https://sipswpcalculator.com/about",
  "jobTitle": "Software Engineer & Finance Enthusiast",
  "description": "Creator of SIP & SWP Calculator. Passionate about making financial planning accessible through technology.",
  "knowsAbout": ["Systematic Investment Plans", "Systematic Withdrawal Plans", "Mutual Fund Investing", "Financial Planning", "Tax-Efficient Investing", "Web Development"],
  "sameAs": ["https://www.linkedin.com/in/sumeet-boga/"]
}
</script>
<style>
    /* Premium layout animations and custom decorative assets */
    .profile-card:hover .profile-ring {
        transform: scale(1.05) rotate(5deg);
        border-color: var(--color-acc-growth);
    }
    .timeline-dot::after {
        content: '';
        position: absolute;
        width: 8px;
        height: 8px;
        background-color: #10b981;
        border-radius: 50%;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
    }
    .hover-lift {
        transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.3s ease;
    }
    .hover-lift:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 30px -10px rgba(16, 185, 129, 0.15);
    }
</style>
<?php
$page_config['additional_head'] = ob_get_clean();
$active_page = 'about.php';
ob_start();
?>

<?php include __DIR__ . '/../components/theme-glow-blobs.php'; ?>

<div class="max-w-5xl mx-auto px-4 sm:px-6 pb-20 relative">
    
    <?php
    $hero = [
        'badge' => 'Meet the Builder',
        'title_prefix' => 'About ',
        'title_highlight' => 'Me',
        'title_suffix' => ' & The Project',
        'description' => 'The engineering journey behind a transparent, ad-free financial planning suite built for smart investors.'
    ];
    include __DIR__ . '/../components/page-hero.php';
    ?>

    <!-- Creator Bio Card & Introduction -->
    <section class="grid grid-cols-1 md:grid-cols-12 gap-8 items-stretch mb-16">
        
        <!-- Interactive Profile Card -->
        <div class="md:col-span-4 flex flex-col items-center text-center p-8 bg-white border border-slate-200/80 rounded-3xl shadow-sm relative overflow-hidden group profile-card">
            <div class="absolute top-0 left-0 w-full h-2 bg-gradient-to-r from-emerald-500 to-teal-500"></div>
            
            <div class="relative w-32 h-32 mb-6">
                <div class="absolute inset-0 rounded-full border-2 border-dashed border-slate-200 profile-ring transition-transform duration-500"></div>
                <img src="/assets/sumeet-boga.jpg" alt="Sumeet Boga — Creator of SIP & SWP Calculator"
                     class="absolute inset-2 w-28 h-28 rounded-full object-cover border border-slate-100 shadow-inner group-hover:scale-102 transition-transform duration-300">
            </div>

            <h2 class="text-xl font-bold text-slate-800 leading-tight">Sumeet Boga</h2>
            <p class="text-sm text-emerald-600 font-semibold mt-1 mb-4">Software Engineer &amp; Builder</p>
            
            <p class="text-xs text-slate-500 leading-relaxed mb-6 font-medium">
                Combining tech expertise with personal finance passion to build fast, secure calculators.
            </p>

            <a href="https://www.linkedin.com/in/sumeet-boga/" target="_blank" rel="noopener"
               class="inline-flex items-center justify-center gap-2 w-full py-2.5 px-4 text-sm font-semibold rounded-xl text-white bg-[#0077b5] hover:bg-[#006297] active:scale-98 transition-all shadow-sm shadow-[#0077b5]/10">
                <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24">
                    <path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z" />
                </svg>
                Connect on LinkedIn
            </a>
        </div>

        <!-- Professional Story -->
        <div class="md:col-span-8 p-8 md:p-10 bg-white border border-slate-200/80 rounded-3xl shadow-sm flex flex-col justify-between">
            <div>
                <h3 class="text-2xl font-bold text-slate-800 mb-4 flex items-center gap-2">
                    <span class="text-emerald-500">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                        </svg>
                    </span>
                    The Vision Behind the Tools
                </h3>
                <div class="text-slate-600 space-y-4 leading-relaxed font-medium text-sm sm:text-base">
                    <p>
                        In personal finance, clarity is everything. Unfortunately, many online calculators are slow, complex, or designed to capture your contact details and pitch you insurance policies.
                    </p>
                    <p>
                        I wanted a clean tool that did two things exceptionally well: model **Step-up Systematic Investment Plans (SIP)** to match salary increments, and simulation-test a **Systematic Withdrawal Plan (SWP)** for retirement income under inflation.
                    </p>
                    <p>
                        Unable to find a transparent, lightweight suite, I decided to build it. This calculator utilizes 100% local, client-side browser logic to keep your numbers completely private, running smoothly without any corporate telemetry.
                    </p>
                </div>
            </div>
            
            <div class="mt-6 pt-6 border-t border-slate-100 flex flex-wrap gap-x-6 gap-y-2 text-xs font-semibold text-slate-400">
                <span class="flex items-center gap-1.5"><span class="w-1.5 h-1.5 rounded-full bg-slate-300"></span> 100% Free &amp; Open Logic</span>
                <span class="flex items-center gap-1.5"><span class="w-1.5 h-1.5 rounded-full bg-slate-300"></span> Local-First Architecture</span>
                <span class="flex items-center gap-1.5"><span class="w-1.5 h-1.5 rounded-full bg-slate-300"></span> Verified Math</span>
            </div>
        </div>
    </section>

    <!-- Project Milestones / Timeline -->
    <section class="mb-16">
        <h3 class="text-2xl font-bold text-slate-800 text-center mb-10">Project Journey &amp; Milestones</h3>
        <div class="relative max-w-2xl mx-auto pl-8 border-l border-slate-200 space-y-10">
            
            <!-- Milestone 1 -->
            <div class="relative">
                <span class="absolute -left-12 top-1.5 w-8 h-8 rounded-full border border-emerald-200 bg-emerald-50 flex items-center justify-center timeline-dot"></span>
                <h4 class="text-base font-bold text-slate-800">Conception &amp; Engine Prototype</h4>
                <span class="text-xs text-emerald-600 font-bold bg-emerald-50 border border-emerald-100/50 px-2 py-0.5 rounded-md inline-block mt-0.5 mb-2">Q2 2024</span>
                <p class="text-sm text-slate-500 font-medium leading-relaxed">
                    Designed the initial compound interest and step-up algorithms. Verified formulas against Excel sheets and standard financial reference papers to ensure absolute math consistency.
                </p>
            </div>
            
            <!-- Milestone 2 -->
            <div class="relative">
                <span class="absolute -left-12 top-1.5 w-8 h-8 rounded-full border border-slate-200 bg-slate-50 flex items-center justify-center timeline-dot"></span>
                <h4 class="text-base font-bold text-slate-800">SIP + SWP Unified Simulator</h4>
                <span class="text-xs text-slate-500 font-bold bg-slate-100 border border-slate-200/50 px-2 py-0.5 rounded-md inline-block mt-0.5 mb-2">Q1 2025</span>
                <p class="text-sm text-slate-500 font-medium leading-relaxed">
                    Launched the first integrated calculator that maps the wealth accumulation phase (SIP) directly into the retired distribution phase (SWP) in a single visual chart.
                </p>
            </div>
            
            <!-- Milestone 3 -->
            <div class="relative">
                <span class="absolute -left-12 top-1.5 w-8 h-8 rounded-full border border-emerald-200 bg-emerald-50 flex items-center justify-center timeline-dot"></span>
                <h4 class="text-base font-bold text-slate-800">Advanced Mutual Fund Taxation Engine</h4>
                <span class="text-xs text-emerald-600 font-bold bg-emerald-50 border border-emerald-100/50 px-2 py-0.5 rounded-md inline-block mt-0.5 mb-2">Q1 2026</span>
                <p class="text-sm text-slate-500 font-medium leading-relaxed">
                    Integrated accurate LTCG and STCG tax rules (compliant with Indian tax policies) so users can model tax-efficient withdrawal structures instead of raw returns.
                </p>
            </div>
        </div>
    </section>

    <!-- Key Pillars / Feature Grid -->
    <section class="mb-16">
        <h3 class="text-2xl font-bold text-slate-800 text-center mb-10">What Makes This Suite Unique</h3>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            
            <!-- Pillar 1 -->
            <div class="p-6 bg-white border border-slate-200/80 rounded-2xl shadow-sm flex gap-4 hover-lift">
                <div class="flex-shrink-0 w-12 h-12 rounded-xl bg-emerald-50 border border-emerald-100 flex items-center justify-center text-emerald-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                    </svg>
                </div>
                <div>
                    <h4 class="font-bold text-slate-800 text-sm mb-1.5">True Annual Step-Up</h4>
                    <p class="text-xs text-slate-500 font-medium leading-relaxed">
                        Increment your investments on a set percentage yearly. This realistic simulation mirrors actual career progression and wage hikes over time.
                    </p>
                </div>
            </div>

            <!-- Pillar 2 -->
            <div class="p-6 bg-white border border-slate-200/80 rounded-2xl shadow-sm flex gap-4 hover-lift">
                <div class="flex-shrink-0 w-12 h-12 rounded-xl bg-emerald-50 border border-emerald-100 flex items-center justify-center text-emerald-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2" />
                    </svg>
                </div>
                <div>
                    <h4 class="font-bold text-slate-800 text-sm mb-1.5">Unified Accumulation &amp; Drawdown</h4>
                    <p class="text-xs text-slate-500 font-medium leading-relaxed">
                        Transition seamlessly from compounding your savings into receiving a tax-efficient retirement salary, all mapped out visually.
                    </p>
                </div>
            </div>

            <!-- Pillar 3 -->
            <div class="p-6 bg-white border border-slate-200/80 rounded-2xl shadow-sm flex gap-4 hover-lift">
                <div class="flex-shrink-0 w-12 h-12 rounded-xl bg-emerald-50 border border-emerald-100 flex items-center justify-center text-emerald-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <h4 class="font-bold text-slate-800 text-sm mb-1.5">Inflation Adjustments</h4>
                    <p class="text-xs text-slate-500 font-medium leading-relaxed">
                        Avoid purchasing power erosion. Adjust retirement income yearly so your real spending capacity remains constant across decades.
                    </p>
                </div>
            </div>

            <!-- Pillar 4 -->
            <div class="p-6 bg-white border border-slate-200/80 rounded-2xl shadow-sm flex gap-4 hover-lift">
                <div class="flex-shrink-0 w-12 h-12 rounded-xl bg-emerald-50 border border-emerald-100 flex items-center justify-center text-emerald-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </div>
                <div>
                    <h4 class="font-bold text-slate-800 text-sm mb-1.5">100% Client-Side Privacy</h4>
                    <p class="text-xs text-slate-500 font-medium leading-relaxed">
                        Your parameters are yours alone. Calculations occur inside your browser session. No data is stored, sold, or shared with third parties.
                    </p>
                </div>
            </div>

        </div>
    </section>

    <!-- Accuracy & Disclaimer -->
    <section class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-16">
        
        <!-- Accuracy Details -->
        <div class="p-6 bg-white border border-slate-200/80 rounded-2xl shadow-sm">
            <div class="flex items-center gap-3 mb-4">
                <span class="flex items-center justify-center w-8 h-8 rounded-full bg-emerald-50 border border-emerald-100 text-emerald-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </span>
                <h4 class="font-bold text-slate-800 text-sm">Commitment to Accuracy</h4>
            </div>
            
            <p class="text-xs text-slate-500 leading-relaxed font-medium mb-4">
                Financial models and calculations are calibrated using framework policies and regulatory math guidelines established by key financial institutions:
            </p>
            
            <ul class="grid grid-cols-3 gap-2 text-xs font-bold text-slate-600 mb-4">
                <li>
                    <a href="https://www.amfiindia.com/" target="_blank" rel="noopener noreferrer" class="flex flex-col p-2.5 bg-slate-50 hover:bg-emerald-50 border border-slate-100 hover:border-emerald-100 rounded-xl text-center group transition-colors">
                        <span class="group-hover:text-emerald-600 transition-colors">AMFI</span>
                        <span class="text-[10px] text-slate-400 font-medium mt-0.5">India</span>
                    </a>
                </li>
                <li>
                    <a href="https://www.sebi.gov.in/" target="_blank" rel="noopener noreferrer" class="flex flex-col p-2.5 bg-slate-50 hover:bg-emerald-50 border border-slate-100 hover:border-emerald-100 rounded-xl text-center group transition-colors">
                        <span class="group-hover:text-emerald-600 transition-colors">SEBI</span>
                        <span class="text-[10px] text-slate-400 font-medium mt-0.5">Regulations</span>
                    </a>
                </li>
                <li>
                    <a href="https://incometaxindia.gov.in/" target="_blank" rel="noopener noreferrer" class="flex flex-col p-2.5 bg-slate-50 hover:bg-emerald-50 border border-slate-100 hover:border-emerald-100 rounded-xl text-center group transition-colors">
                        <span class="group-hover:text-emerald-600 transition-colors">ITD</span>
                        <span class="text-[10px] text-slate-400 font-medium mt-0.5">Tax Laws</span>
                    </a>
                </li>
            </ul>
            
            <span class="text-xs text-emerald-700 bg-emerald-50 border border-emerald-100/60 px-3 py-1 rounded-lg font-bold inline-block">
                Last math audit: March 2026
            </span>
        </div>

        <!-- Disclaimer Details -->
        <div class="p-6 bg-white border border-slate-200/80 rounded-2xl shadow-sm flex flex-col justify-between">
            <div>
                <div class="flex items-center gap-3 mb-4">
                    <span class="flex items-center justify-center w-8 h-8 rounded-full bg-slate-50 border border-slate-100 text-slate-500">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </span>
                    <h4 class="font-bold text-slate-800 text-sm">Regulatory Disclaimer</h4>
                </div>
                
                <p class="text-xs text-slate-500 leading-relaxed font-medium">
                    This suite is built for educational and planning purposes only. The mathematical simulations do not account for external costs like exit loads, scheme expense ratios, or severe market downturns. I am not a SEBI-registered advisor; consult an official advisor before executing investments.
                </p>
            </div>
            
            <div class="mt-4 pt-4 border-t border-slate-100 text-xs font-semibold text-slate-400 flex items-center justify-between">
                <span>Have math feedback?</span>
                <a href="mailto:help@sipswpcalculator.com" class="text-emerald-600 hover:text-emerald-700 font-bold underline transition-colors">
                    help@sipswpcalculator.com
                </a>
            </div>
        </div>
        
    </section>

    <!-- Bottom CTA: Quick Navigation -->
    <section class="p-8 md:p-10 bg-gradient-to-br from-slate-900 via-slate-800 to-emerald-950 text-white rounded-3xl border border-slate-800 shadow-xl relative overflow-hidden text-center">
        <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,rgba(16,185,129,0.1),transparent)] pointer-events-none"></div>
        
        <h3 class="text-2xl sm:text-3xl font-extrabold mb-3">Ready to plan your financial milestones?</h3>
        <p class="text-slate-400 text-sm sm:text-base max-w-xl mx-auto mb-8 font-medium leading-relaxed">
            Jump straight into one of our high-fidelity calculators and map your path to wealth.
        </p>
        
        <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
            <a href="/sip-calculator" class="inline-flex items-center justify-center gap-2 py-3 px-6 text-sm font-bold bg-emerald-500 hover:bg-emerald-400 text-slate-950 rounded-xl transition-all hover:scale-102 hover:shadow-lg hover:shadow-emerald-500/20 w-full sm:w-auto">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                </svg>
                SIP Compounding Tool
            </a>
            <a href="/retirement-drawdown-planner" class="inline-flex items-center justify-center gap-2 py-3 px-6 text-sm font-bold bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-200 hover:text-white rounded-xl transition-all hover:scale-102 w-full sm:w-auto">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Retirement Drawdown
            </a>
        </div>
    </section>

</div>

<?php
$page_content = ob_get_clean();
require_once __DIR__ . '/../layouts/layout.php';
?>