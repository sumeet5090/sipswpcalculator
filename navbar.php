<?php
$current_page = basename($_SERVER['PHP_SELF']);
// Map clean URLs if rewrite rules are in effect (fallback for exact matches)
if ($current_page == 'index.php' || $current_page == '')
    $current_page = 'index.php';
if ($current_page == 'goal-reverser.php')
    $current_page = 'goal-reverser.php';
if ($current_page == 'sequence-risk-analyzer.php')
    $current_page = 'sequence-risk-analyzer.php';
if ($current_page == 'swp-heatmap.php')
    $current_page = 'swp-heatmap.php';
?>

<nav class="navbar-glass fixed w-full z-50 top-0 start-0 border-b border-gray-200/50 transition-all duration-300">
    <div class="max-w-7xl mx-auto flex flex-wrap items-center justify-between p-4">
        <a href="/" class="flex items-center space-x-3 rtl:space-x-reverse group">
            <div
                class="p-2 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-lg shadow-lg group-hover:shadow-indigo-500/30 transition-all duration-300">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 7h6m0 3.665V17h4.335M19 19h-1.665"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4"></path>
                </svg>
            </div>
            <span class="self-center text-xl font-bold whitespace-nowrap text-gray-800 tracking-tight">SIP<span
                    class="text-indigo-600">Calculator</span></span>
        </a>
        <button data-collapse-toggle="navbar-default" type="button"
            class="inline-flex items-center p-2 w-10 h-10 justify-center text-sm text-gray-500 rounded-lg md:hidden hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-200"
            aria-controls="navbar-default" aria-expanded="false"
            onclick="document.getElementById('navbar-default').classList.toggle('hidden')">
            <span class="sr-only">Open main menu</span>
            <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 17 14">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M1 1h15M1 7h15M1 13h15" />
            </svg>
        </button>
        <div class="hidden w-full md:block md:w-auto" id="navbar-default">
            <ul
                class="font-medium flex flex-col p-4 md:p-0 mt-4 border border-gray-100 rounded-xl md:flex-row md:space-x-8 rtl:space-x-reverse md:mt-0 md:border-0">
                <li>
                    <a href="/"
                        class="block py-2 px-4 rounded-lg md:p-0 transition-all duration-200 <?= ($current_page == 'index.php') ? 'text-white bg-indigo-600 md:bg-transparent md:text-indigo-600 md:font-bold' : 'text-gray-900 hover:bg-gray-100 md:hover:bg-transparent md:hover:text-indigo-600' ?>"
                        aria-current="page">Calculator</a>
                </li>
                <li>
                    <a href="goal-reverser"
                        class="block py-2 px-4 rounded-lg md:p-0 transition-all duration-200 <?= ($current_page == 'goal-reverser.php') ? 'text-white bg-indigo-600 md:bg-transparent md:text-indigo-600 md:font-bold' : 'text-gray-900 hover:bg-gray-100 md:hover:bg-transparent md:hover:text-indigo-600' ?>">Goal
                        Reverser</a>
                </li>
                <li>
                    <a href="sequence-risk-analyzer"
                        class="block py-2 px-4 rounded-lg md:p-0 transition-all duration-200 <?= ($current_page == 'sequence-risk-analyzer.php') ? 'text-white bg-indigo-600 md:bg-transparent md:text-indigo-600 md:font-bold' : 'text-gray-900 hover:bg-gray-100 md:hover:bg-transparent md:hover:text-indigo-600' ?>">Risk
                        Analyzer</a>
                </li>
                <li>
                    <a href="swp-heatmap"
                        class="block py-2 px-4 rounded-lg md:p-0 transition-all duration-200 <?= ($current_page == 'swp-heatmap.php') ? 'text-white bg-indigo-600 md:bg-transparent md:text-indigo-600 md:font-bold' : 'text-gray-900 hover:bg-gray-100 md:hover:bg-transparent md:hover:text-indigo-600' ?>">SWP
                        Heatmap</a>
                </li>
            </ul>
        </div>
    </div>
</nav>
<!-- Spacer to prevent content overlap -->
<div class="h-20"></div>