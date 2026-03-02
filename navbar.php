<?php
$current_page = basename($_SERVER['PHP_SELF']);
if ($current_page == 'index.php' || $current_page == '')
    $current_page = 'index.php';
?>

<style>
    /* Desktop Guides dropdown - pure CSS, no Tailwind group-hover needed */
    .nav-dropdown .nav-dropdown-menu {
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.2s, visibility 0.2s;
    }

    .nav-dropdown:hover .nav-dropdown-menu {
        opacity: 1;
        visibility: visible;
    }

    .nav-dropdown:hover .nav-chevron {
        transform: rotate(180deg);
    }
</style>

<nav
    class="navbar-glass fixed w-full z-50 top-0 start-0 border-b border-slate-200 bg-white/80 backdrop-blur-md transition-all duration-300 shadow-sm">
    <div class="max-w-7xl mx-auto flex items-center justify-between p-4">
        <a href="/" class="flex items-center space-x-3 rtl:space-x-reverse">
            <div
                class="p-2 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-lg shadow-lg shadow-emerald-500/20 transition-all duration-300">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="24"
                    height="24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 7h6m0 3.665V17h4.335M19 19h-1.665"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4"></path>
                </svg>
            </div>
            <span class="self-center text-xl font-bold whitespace-nowrap text-slate-800 tracking-tight">SIP<span
                    class="text-emerald-600">Calculator</span></span>
        </a>

        <!-- Desktop nav -->
        <nav class="hidden sm:flex items-center gap-6 text-sm font-medium" aria-label="Main navigation">
            <a href="/"
                class="<?= $current_page === 'index.php' ? 'text-indigo-600 font-semibold' : 'text-slate-600 hover:text-indigo-600' ?> transition-colors">
                Calculator
            </a>
            <a href="/sip-calculator"
                class="<?= $current_page === 'sip-calculator.php' ? 'text-indigo-600 font-semibold' : 'text-slate-600 hover:text-indigo-600' ?> transition-colors">
                SIP Guide
            </a>
            <div class="relative nav-dropdown">
                <button type="button"
                    class="text-slate-600 hover:text-indigo-600 transition-colors inline-flex items-center gap-1">
                    Guides
                    <svg class="w-4 h-4 transition-transform nav-chevron" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div class="absolute right-0 top-full pt-2 w-56 z-50 nav-dropdown-menu">
                    <div class="bg-white rounded-xl shadow-xl border border-slate-200 py-2">
                        <a href="/sip-step-up-calculator"
                            class="block px-4 py-2.5 text-sm text-slate-600 hover:text-indigo-600 hover:bg-indigo-50 transition-colors">Step-Up
                            SIP Guide</a>
                        <a href="/swp-retirement-planning"
                            class="block px-4 py-2.5 text-sm text-slate-600 hover:text-indigo-600 hover:bg-indigo-50 transition-colors">SWP
                            Retirement Planning</a>
                        <a href="/mutual-fund-tax-2026"
                            class="block px-4 py-2.5 text-sm text-slate-600 hover:text-indigo-600 hover:bg-indigo-50 transition-colors">Mutual
                            Fund Tax 2026</a>
                        <a href="/sip-vs-fd-vs-ppf"
                            class="block px-4 py-2.5 text-sm text-slate-600 hover:text-indigo-600 hover:bg-indigo-50 transition-colors">SIP
                            vs FD vs PPF</a>
                        <a href="/swp-tax-calculator"
                            class="block px-4 py-2.5 text-sm text-slate-600 hover:text-indigo-600 hover:bg-indigo-50 transition-colors">SWP
                            Tax Calculator</a>
                        <a href="/compound-interest-calculator"
                            class="block px-4 py-2.5 text-sm text-slate-600 hover:text-indigo-600 hover:bg-indigo-50 transition-colors">Compound
                            Interest Calculator</a>
                        <a href="/swp-vs-fixed-deposit"
                            class="block px-4 py-2.5 text-sm text-slate-600 hover:text-indigo-600 hover:bg-indigo-50 transition-colors">SWP
                            vs Fixed Deposit</a>
                        <a href="/sip-for-beginners"
                            class="block px-4 py-2.5 text-sm text-slate-600 hover:text-indigo-600 hover:bg-indigo-50 transition-colors">SIP
                            for Beginners</a>
                        <a href="/inflation-impact-on-sip"
                            class="block px-4 py-2.5 text-sm text-slate-600 hover:text-indigo-600 hover:bg-indigo-50 transition-colors">Inflation
                            Impact on SIP</a>
                        <div class="border-t border-slate-100 my-1"></div>
                        <a href="/about"
                            class="block px-4 py-2.5 text-sm text-slate-600 hover:text-indigo-600 hover:bg-indigo-50 transition-colors">About
                            Us</a>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Mobile hamburger button -->
        <button id="mobile-menu-btn" type="button"
            class="sm:hidden inline-flex items-center justify-center p-2 rounded-lg text-slate-500 hover:text-slate-700 hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors"
            aria-controls="mobile-menu" aria-expanded="false" aria-label="Toggle navigation menu">
            <svg id="hamburger-icon" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16">
                </path>
            </svg>
            <svg id="close-icon" class="w-6 h-6 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
    </div>

    <!-- Mobile menu dropdown -->
    <div id="mobile-menu" class="sm:hidden hidden border-t border-slate-200 bg-white/95 backdrop-blur-md">
        <div class="px-4 py-3 space-y-1">
            <a href="/"
                class="block px-3 py-3 rounded-lg text-base font-medium <?= $current_page === 'index.php' ? 'text-indigo-600 bg-indigo-50' : 'text-slate-600 hover:text-indigo-600 hover:bg-slate-50' ?> transition-colors">
                Calculator
            </a>
            <a href="/sip-calculator"
                class="block px-3 py-3 rounded-lg text-base font-medium <?= $current_page === 'sip-calculator.php' ? 'text-indigo-600 bg-indigo-50' : 'text-slate-600 hover:text-indigo-600 hover:bg-slate-50' ?> transition-colors">
                SIP Guide
            </a>
            <button id="mobile-guides-btn" type="button"
                class="w-full flex items-center justify-between px-3 py-3 rounded-lg text-base font-medium text-slate-600 hover:text-indigo-600 hover:bg-slate-50 transition-colors">
                Guides
                <svg id="mobile-guides-icon" class="w-4 h-4 transition-transform duration-200" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>
            <div id="mobile-guides-menu" class="hidden pl-4 pr-2 py-1 space-y-1 bg-slate-50/50 rounded-lg">
                <a href="/sip-step-up-calculator"
                    class="block px-3 py-2.5 rounded-lg text-sm text-slate-600 hover:text-indigo-600 hover:bg-slate-100 transition-colors">Step-Up
                    SIP Guide</a>
                <a href="/swp-retirement-planning"
                    class="block px-3 py-2.5 rounded-lg text-sm text-slate-600 hover:text-indigo-600 hover:bg-slate-100 transition-colors">SWP
                    Retirement Planning</a>
                <a href="/mutual-fund-tax-2026"
                    class="block px-3 py-2.5 rounded-lg text-sm text-slate-600 hover:text-indigo-600 hover:bg-slate-100 transition-colors">Mutual
                    Fund Tax 2026</a>
                <a href="/sip-vs-fd-vs-ppf"
                    class="block px-3 py-2.5 rounded-lg text-sm text-slate-600 hover:text-indigo-600 hover:bg-slate-100 transition-colors">SIP
                    vs FD vs PPF</a>
                <a href="/swp-tax-calculator"
                    class="block px-3 py-2.5 rounded-lg text-sm text-slate-600 hover:text-indigo-600 hover:bg-slate-100 transition-colors">SWP
                    Tax Calculator</a>
                <a href="/compound-interest-calculator"
                    class="block px-3 py-2.5 rounded-lg text-sm text-slate-600 hover:text-indigo-600 hover:bg-slate-100 transition-colors">Compound
                    Interest Calculator</a>
                <a href="/swp-vs-fixed-deposit"
                    class="block px-3 py-2.5 rounded-lg text-sm text-slate-600 hover:text-indigo-600 hover:bg-slate-100 transition-colors">SWP
                    vs Fixed Deposit</a>
                <a href="/sip-for-beginners"
                    class="block px-3 py-2.5 rounded-lg text-sm text-slate-600 hover:text-indigo-600 hover:bg-slate-100 transition-colors">SIP
                    for Beginners</a>
                <a href="/inflation-impact-on-sip"
                    class="block px-3 py-2.5 rounded-lg text-sm text-slate-600 hover:text-indigo-600 hover:bg-slate-100 transition-colors">Inflation
                    Impact on SIP</a>
            </div>
            <div class="border-t border-slate-100 my-1"></div>
            <a href="/about"
                class="block px-3 py-2.5 rounded-lg text-sm text-slate-600 hover:text-indigo-600 hover:bg-slate-50 transition-colors">About
                Us</a>
        </div>
    </div>
</nav>
<!-- Spacer to prevent content overlap -->
<div class="h-16"></div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var btn = document.getElementById('mobile-menu-btn');
        var menu = document.getElementById('mobile-menu');
        var hamburger = document.getElementById('hamburger-icon');
        var closeIcon = document.getElementById('close-icon');
        if (btn && menu) {
            btn.addEventListener('click', function () {
                var isOpen = !menu.classList.contains('hidden');
                menu.classList.toggle('hidden');
                hamburger.classList.toggle('hidden');
                closeIcon.classList.toggle('hidden');
                btn.setAttribute('aria-expanded', !isOpen);
            });
        }

        var guidesBtn = document.getElementById('mobile-guides-btn');
        var guidesMenu = document.getElementById('mobile-guides-menu');
        var guidesIcon = document.getElementById('mobile-guides-icon');
        if (guidesBtn && guidesMenu) {
            guidesBtn.addEventListener('click', function () {
                guidesMenu.classList.toggle('hidden');
                guidesIcon.classList.toggle('rotate-180');
            });
        }
    });
</script>