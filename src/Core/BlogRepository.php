<?php
declare(strict_types=1);

namespace Core;

class BlogRepository {
    public static function getCategories(): array {
        return [
            'growth' => [
                'title' => 'Wealth Growth',
                'desc' => 'Master the compounding engines that drive 20-year wealth building for the modern investor.',
                'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>',
                'accent' => 'emerald'
            ],
            'retirement' => [
                'title' => 'Retirement Hub',
                'desc' => 'Expert withdrawal strategies for generating reliable monthly income without outliving your savings.',
                'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
                'accent' => 'indigo'
            ],
            'comparison' => [
                'title' => 'Strategy Center',
                'desc' => 'Direct comparisons between major investment vehicles and navigating the current tax landscape.',
                'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>',
                'accent' => 'amber'
            ]
        ];
    }

    public static function getAllPosts(): array {
        return [
            // Growth Category (Emerald Accents)
            ['category' => 'growth', 'id' => 'growth', 'tag' => 'Beginner', 'tag_color' => 'emerald', 'title' => 'SIP for Beginners: Complete Guide (2026)', 'desc' => 'Everything you need to know to start your first SIP today — KYC, fund selection, and the step-up strategy explained simply.', 'href' => '/resource/growth/sip-for-beginners', 'featured' => true, 'read_time' => '12 min', 'date' => 'March 2026'],
            ['category' => 'growth', 'id' => 'growth', 'tag' => 'Strategy', 'tag_color' => 'emerald', 'title' => 'The 20-Year Wealth Blueprint', 'desc' => 'How a simple 10% annual step-up SIP can more than double your final corpus over two decades.', 'href' => '/resource/growth/20-year-wealth-blueprint-step-up-sip', 'read_time' => '8 min', 'date' => 'February 2026'],
            ['category' => 'growth', 'id' => 'growth', 'tag' => 'Milestone', 'tag_color' => 'emerald', 'title' => 'How to Reach ₹1 Crore in 15 to 20 Years via SIP', 'desc' => 'Hitting the ₹1 Crore milestone is a matter of math and consistency. Model exactly what you need monthly.', 'href' => '/resource/growth/reach-1-crore-rupees-via-sip', 'read_time' => '10 min', 'date' => 'January 2026'],
            ['category' => 'growth', 'id' => 'growth', 'tag' => 'Inflation', 'tag_color' => 'emerald', 'title' => 'The Silent Killer: Inflation Impact on SIP', 'desc' => 'Why flat SIPs guarantee your purchasing power erodes — and the step-up strategy that fights back.', 'href' => '/resource/growth/inflation-impact-on-sip', 'read_time' => '7 min', 'date' => 'March 2026'],
            ['category' => 'growth', 'id' => 'growth', 'tag' => 'Blueprint', 'tag_color' => 'emerald', 'title' => 'Earning ₹30,000/Month at Age 25? Here is Your Wealth Creation Blueprint', 'desc' => 'A step-by-step financial plan to start investing for long-term compounding, allocate across SIPs, emergency funds, and leverage the power of starting early.', 'href' => '/resource/growth/earning-30k-at-25-investment-blueprint', 'read_time' => '10 min', 'date' => 'July 2026'],

            // Retirement Category (Indigo Accents)
            ['category' => 'retirement', 'id' => 'retirement', 'tag' => 'Strategy', 'tag_color' => 'indigo', 'title' => 'The 4% SWP Rule Explained', 'desc' => 'Structure your retirement withdrawals to ensure your money outlasts you. Master the 4% rule.', 'href' => '/resource/retirement/retirement-planning-4-percent-swp-rule', 'featured' => true, 'read_time' => '15 min', 'date' => 'March 2026'],
            ['category' => 'retirement', 'id' => 'retirement', 'tag' => 'Lifecycle', 'tag_color' => 'indigo', 'title' => 'SIP vs. SWP: Build & Enjoy', 'desc' => 'The full lifecycle: how to transition from your SIP accumulation phase into a sustainable SWP income stream.', 'href' => '/resource/retirement/sip-vs-swp-wealth-creation-withdrawal-strategy', 'read_time' => '9 min', 'date' => 'February 2026'],
            ['category' => 'retirement', 'id' => 'retirement', 'tag' => 'Planning', 'tag_color' => 'indigo', 'title' => 'SWP Retirement Planning Guide', 'desc' => 'A complete guide to using Systematic Withdrawal Plans to generate reliable monthly income in retirement.', 'href' => '/resource/retirement/swp-retirement-planning', 'read_time' => '13 min', 'date' => 'March 2026'],

            // Comparison Category (Amber Accents)
            ['category' => 'comparison', 'id' => 'comparison', 'tag' => 'Comparison', 'tag_color' => 'amber', 'title' => 'SIP vs FD vs PPF: A Direct Comparison', 'desc' => 'Returns, risk, liquidity, and tax compared across major investment instruments. Choose the right path.', 'href' => '/resource/comparison/sip-vs-fd-vs-ppf', 'featured' => true, 'read_time' => '10 min', 'date' => 'March 2026'],
            ['category' => 'comparison', 'id' => 'comparison', 'tag' => 'Comparison', 'tag_color' => 'amber', 'title' => 'SWP vs Fixed Deposit: Which Wins?', 'desc' => 'Head-to-head analysis of SWP from mutual funds vs. bank FDs for generating retirement income.', 'href' => '/resource/comparison/swp-vs-fixed-deposit', 'read_time' => '8 min', 'date' => 'February 2026'],
            ['category' => 'comparison', 'id' => 'comparison', 'tag' => 'Comparison', 'tag_color' => 'amber', 'title' => 'SWP vs Annuity 2026', 'desc' => 'Should you buy an annuity or run an SWP? An honest, data-driven comparison for 2026.', 'href' => '/resource/comparison/swp-vs-annuity-2026', 'read_time' => '7 min', 'date' => 'January 2026'],
            ['category' => 'comparison', 'id' => 'comparison', 'tag' => 'Tax', 'tag_color' => 'amber', 'title' => 'Mutual Fund Tax Guide 2026', 'desc' => 'LTCG, STCG, ELSS, and indexation — everything you need after the 2026 budget changes.', 'href' => '/resource/comparison/mutual-fund-tax-2026', 'read_time' => '11 min', 'date' => 'March 2026'],
            ['category' => 'comparison', 'id' => 'comparison', 'tag' => 'Benchmarks', 'tag_color' => 'amber', 'title' => 'Mutual Fund Returns Benchmarks 2026', 'desc' => 'Historical benchmark tables for Indian mutual funds. Learn how to select a realistic return rate for your planning.', 'href' => '/resource/comparison/mf-returns-benchmarks', 'read_time' => '9 min', 'date' => 'January 2026'],
        ];
    }
}
