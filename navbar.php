<?php
$current_page = basename($_SERVER['PHP_SELF']);
if ($current_page == 'index.php' || $current_page == '')
    $current_page = 'index.php';
?>

<style>
    /* Desktop Guides dropdown - pure CSS hover */
    .nav-dropdown .nav-dropdown-menu {
        opacity: 0;
        visibility: hidden;
        transform: translateY(4px);
        transition: opacity 0.2s, visibility 0.2s, transform 0.2s;
    }

    .nav-dropdown:hover .nav-dropdown-menu {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }

    .nav-dropdown:hover .nav-chevron {
        transform: rotate(180deg);
    }
</style>

<nav
    class="navbar-glass fixed w-full z-50 top-0 start-0 border-b border-slate-200 bg-white/80 backdrop-blur-md transition-all duration-300 shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center justify-between py-4">
        <a href="/#calculator-section" class="flex items-center space-x-3 rtl:space-x-reverse group">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                style="width: 40px; height: 40px;"
                class="rounded-xl shadow-lg shadow-emerald-500/30 transition-transform duration-300 group-hover:scale-105"
                role="img" aria-label="SIP SWP Calculator Logo">
                <rect width="24" height="24" rx="6" fill="url(#logo-grad-navbar)" />
                <defs>
                    <linearGradient id="logo-grad-navbar" x1="0%" y1="100%" x2="100%" y2="0%">
                        <stop offset="0%" stop-color="#059669" />
                        <stop offset="100%" stop-color="#2dd4bf" />
                    </linearGradient>
                </defs>
                <path fill="none" stroke="#ffffff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"
                    d="M4 13l5-5 3.5 3.5 7.5-7.5" />
                <path fill="none" stroke="#ffffff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"
                    d="M15 4h5v5" />
                <path fill="none" stroke="#ffffff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"
                    stroke-opacity="0.5" d="M4 17l5-5 3.5 3.5 7.5-7.5" />
                <path fill="none" stroke="#ffffff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"
                    stroke-opacity="0.25" d="M4 21l5-5 3.5 3.5 7.5-7.5" />
            </svg>
            <span class="self-center text-xl font-extrabold whitespace-nowrap tracking-tight text-slate-800">SIP &amp; SWP <span class="text-emerald-500 font-medium">Calculator</span></span>
        </a>

        <!-- Desktop nav -->
        <nav class="hidden sm:flex items-center gap-6 text-sm font-medium" aria-label="Main navigation">
            <a href="/#calculator-section"
                class="<?= $current_page === 'index.php' ? 'text-emerald-600 font-semibold' : 'text-slate-600 hover:text-emerald-600' ?> transition-colors">
                Calculator
            </a>
            <a href="/resources.php"
                class="<?= $current_page === 'resources.php' ? 'text-emerald-600 font-semibold' : 'text-slate-600 hover:text-emerald-600' ?> transition-colors">
                Resources
            </a>
            <a href="/glossary.php"
                class="<?= $current_page === 'glossary.php' ? 'text-emerald-600 font-semibold' : 'text-slate-600 hover:text-emerald-600' ?> transition-colors">
                Glossary
            </a>
            <a href="/about"
                class="<?= $current_page === 'about.php' ? 'text-emerald-600 font-semibold' : 'text-slate-600 hover:text-emerald-600' ?> transition-colors">
                About
            </a>
        </nav>

        <!-- Mobile hamburger button -->
        <button id="mobile-menu-btn" type="button"
            class="sm:hidden inline-flex items-center justify-center p-2 rounded-lg text-slate-500 hover:text-slate-700 hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors"
            aria-controls="mobile-menu" aria-expanded="false" aria-label="Toggle navigation menu">
            <svg id="hamburger-icon" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16">
                </path>
            </svg>
            <svg id="close-icon" class="w-6 h-6 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
    </div>

    <!-- Mobile menu dropdown -->
    <div id="mobile-menu" class="sm:hidden hidden border-t border-slate-200 bg-white/95 backdrop-blur-md">
        <div class="px-4 py-3 space-y-1">
            <a href="/#calculator-section"
                class="block px-3 py-3 rounded-lg text-base font-medium <?= $current_page === 'index.php' ? 'text-emerald-600 bg-emerald-50' : 'text-slate-600 hover:text-emerald-600 hover:bg-slate-50' ?> transition-colors">
                Calculator
            </a>
            <a href="/resources.php"
                class="block px-3 py-3 rounded-lg text-base font-medium <?= $current_page === 'resources.php' ? 'text-emerald-600 bg-emerald-50' : 'text-slate-600 hover:text-emerald-600 hover:bg-slate-50' ?> transition-colors">
                Resources
            </a>
            <a href="/glossary.php"
                class="block px-3 py-3 rounded-lg text-base font-medium <?= $current_page === 'glossary.php' ? 'text-emerald-600 bg-emerald-50' : 'text-slate-600 hover:text-emerald-600 hover:bg-slate-50' ?> transition-colors">
                Glossary
            </a>
            <div class="border-t border-slate-100 my-1"></div>
            <a href="/about"
                class="block px-3 py-3 rounded-lg text-base font-medium <?= $current_page === 'about.php' ? 'text-emerald-600 bg-emerald-50' : 'text-slate-600 hover:text-emerald-600 hover:bg-slate-50' ?> transition-colors">About
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
    });
</script>