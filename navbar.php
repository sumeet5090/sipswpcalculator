<?php
$current_page = basename($_SERVER['PHP_SELF']);
if ($current_page == 'index.php' || $current_page == '')
    $current_page = 'index.php';
?>

<nav
    class="navbar-glass fixed w-full z-50 top-0 start-0 border-b border-slate-200 bg-white/80 backdrop-blur-md transition-all duration-300 shadow-sm">
    <div class="max-w-7xl mx-auto flex items-center justify-between p-4">
        <a href="/" class="flex items-center space-x-3 rtl:space-x-reverse group">
            <div
                class="p-2 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-lg shadow-lg shadow-emerald-500/20 group-hover:shadow-emerald-500/40 transition-all duration-300">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 7h6m0 3.665V17h4.335M19 19h-1.665"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4"></path>
                </svg>
            </div>
            <span class="self-center text-xl font-bold whitespace-nowrap text-slate-800 tracking-tight">SIP<span
                    class="text-emerald-600">Calculator</span></span>
        </a>
    </div>
</nav>
<!-- Spacer to prevent content overlap -->
<div class="h-16"></div>