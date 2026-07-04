<?php

/**
 * footer.php
 * Centralized footer component.
 */

?>
<footer class="mt-16 text-sm text-gray-600 glass-card p-10">
    <div class="grid grid-cols-2 md:grid-cols-4 gap-8 mb-8">
        <div>
            <h3 class="font-bold text-lg mb-3 text-gray-800">Top Calculators</h3>
            <ul class="space-y-2 text-sm">
                <li><a href="/sip-calculator" class="text-emerald-600 hover:underline">SIP Calculator</a></li>
                <li><a href="/swp-calculator" class="text-emerald-600 hover:underline">SWP Calculator</a></li>
                <li><a href="/sip-step-up-calculator" class="text-emerald-600 hover:underline">Step-Up SIP Calculator</a></li>
                <li><a href="/swp-tax-calculator" class="text-emerald-600 hover:underline">SWP Tax Calculator</a></li>
                <li><a href="/compound-interest-calculator" class="text-emerald-600 hover:underline">Compound Interest</a></li>
                <li><a href="/retirement-drawdown-planner" class="text-emerald-600 hover:underline">Retirement Planner</a></li>
                <li><a href="/lumpsum-calculator" class="text-emerald-600 hover:underline">Lumpsum Calculator</a></li>
                <li><a href="/mutual-fund-calculator" class="text-emerald-600 hover:underline">Mutual Fund Hub</a></li>
            </ul>
        </div>
        <div>
            <h3 class="font-bold text-lg mb-3 text-gray-800">Popular Guides</h3>
            <ul class="space-y-2 text-sm">
                <li><a href="/resource/growth/sip-for-beginners" class="text-emerald-600 hover:underline">SIP for Beginners</a></li>
                <li><a href="/resource/retirement/swp-retirement-planning" class="text-emerald-600 hover:underline">SWP Retirement Planning</a></li>
                <li><a href="/resource/comparison/sip-vs-lumpsum" class="text-emerald-600 hover:underline">SIP vs Lumpsum</a></li>
                <li><a href="/resource/comparison/sip-vs-fd-vs-ppf" class="text-emerald-600 hover:underline">SIP vs FD vs PPF</a></li>
                <li><a href="/resource/retirement/retirement-planning-4-percent-swp-rule" class="text-emerald-600 hover:underline">The 4% SWP Rule</a></li>
                <li><a href="/resources" class="text-emerald-600 hover:underline mt-2 inline-block font-semibold">View All Resources &rarr;</a></li>
            </ul>
        </div>
        <div>
            <h3 class="font-bold text-lg mb-3 text-gray-800">Support & Legal</h3>
            <ul class="space-y-2 text-sm">
                <li><a href="/about" class="text-emerald-600 hover:underline">About Me</a></li>
                <li><a href="/glossary" class="text-emerald-600 hover:underline">Financial Glossary</a></li>
                <li><a href="/faq" class="text-emerald-600 hover:underline">FAQ</a></li>
                <li><a href="mailto:help@sipswpcalculator.com" class="text-emerald-600 hover:underline">Contact Us</a></li>
                <li><a href="/privacy" class="text-emerald-600 hover:underline">Privacy Policy</a></li>
                <li><a href="/terms" class="text-emerald-600 hover:underline">Terms of Service</a></li>
            </ul>
        </div>
        <div>
            <h3 class="font-bold text-lg mb-3 text-gray-800">About Calculator</h3>
            <p class="leading-relaxed">A powerful, free tool designed to help investors visualize and plan their SIP and SWP strategies with precision and ease.</p>
        </div>
    </div>

    <div class="border-t border-gray-200 pt-6">
        <div class="bg-amber-100 p-4 rounded-lg mb-6 border border-amber-200">
            <p class="text-amber-800"><span class="font-bold">⚠️ Disclaimer</span><br>This calculator is for
                educational and illustrative purposes only. It does not constitute financial, tax, or investment
                advice. Past performance is not indicative of future results. Please consult with a qualified
                financial advisor before making any investment decisions. See our <a href="/privacy"
                    class="text-amber-900 underline font-medium">Privacy Policy</a> and <a href="/terms"
                    class="text-amber-900 underline font-medium">Terms of Service</a>.</p>
        </div>

        <div class="text-center">
            <p class="text-xs text-slate-500 font-medium">Verified for Accuracy: March 2026</p>
            <p class="text-xs mt-2">&copy;
                <?= date('Y')?> SIP/SWP Calculator. All rights reserved.
            </p>
            <p class="text-xs mt-2">Built with care for global investors • <span class="text-emerald-600">No
                    tracking • No ads • Free forever</span></p>
        </div>
    </div>
</footer>