<?php
/**
 * header.php
 * Centralized navigation component.
 * Expects $active_page to highlight current menu item.
 */
$active_page = $active_page ?? basename($_SERVER['PHP_SELF']);
if ($active_page === '' || $active_page === '/')
    $active_page = 'index.php';

$nav_items = [
    ['label' => 'Show Calculator', 'href' => '/#calculator-section', 'id' => 'index.php'],
    ['label' => 'Resources', 'href' => '/resources.php', 'id' => 'resources.php'],
    ['label' => 'Glossary', 'href' => '/glossary.php', 'id' => 'glossary.php'],
    ['label' => 'FAQ', 'href' => '/faq.php', 'id' => 'faq.php'],
    ['label' => 'About Me', 'href' => '/about', 'id' => 'about.php'],
];
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
        <a href="/" class="flex items-center space-x-3 rtl:space-x-reverse group">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" style="width: 40px; height: 40px;"
                class="rounded-xl shadow-lg shadow-emerald-500/30 transition-transform duration-300 group-hover:scale-105"
                role="img" aria-label="SIP SWP Calculator Logo">
                <rect width="24" height="24" rx="6" fill="url(#logo-grad-header)" />
                <defs>
                    <linearGradient id="logo-grad-header" x1="0%" y1="100%" x2="100%" y2="0%">
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
            <span class="self-center text-xl font-extrabold whitespace-nowrap tracking-tight text-slate-800">SIP &amp;
                SWP <span class="text-emerald-500 font-medium">Calculator</span></span>
        </a>

        <!-- Desktop nav -->
        <nav class="hidden sm:flex items-center gap-6 text-sm font-medium" aria-label="Main navigation">
            <?php foreach ($nav_items as $item): ?>
            <a href="<?= $item['href']?>"
                class="<?= $active_page === $item['id'] ? 'text-emerald-600 font-semibold' : 'text-slate-600 hover:text-emerald-600'?> transition-colors">
                <?= $item['label']?>
            </a>
            <?php
endforeach; ?>
        </nav>

        <!-- Mobile hamburger button -->
        <button id="mobile-menu-btn" type="button"
            class="sm:hidden inline-flex items-center justify-center p-2 rounded-lg text-slate-500 hover:text-slate-700 hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-colors"
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
            <?php foreach ($nav_items as $item): ?>
            <a href="<?= $item['href']?>"
                class="block px-3 py-3 rounded-lg text-base font-medium <?= $active_page === $item['id'] ? 'text-indigo-600 bg-indigo-50' : 'text-slate-600 hover:text-indigo-600 hover:bg-slate-50'?> transition-colors">
                <?= $item['label']?>
            </a>
            <?php
endforeach; ?>
        </div>
    </div>
</nav>