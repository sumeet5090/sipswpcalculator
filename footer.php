<?php // footer.php ?>
<footer class="mt-16 text-sm text-gray-600 glass-card p-10">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
        <div>
            <h3 class="font-bold text-lg mb-3 text-gray-800">About Calculator</h3>
            <p class="leading-relaxed">A powerful, free tool designed to help investors visualize and plan their
                SIP and SWP strategies with precision and ease.</p>
        </div>
        <div>
            <h3 class="font-bold text-lg mb-3 text-gray-800">Support</h3>
            <p class="leading-relaxed">For questions or feedback about this calculator, <a
                    href="mailto:help@sipswpcalculator.com" class="text-indigo-600 hover:underline">reach out to
                    us</a>. We're here to help you achieve your financial goals.</p>
        </div>
    </div>

    <div class="border-t border-gray-200 pt-6">
        <div class="bg-amber-100 p-4 rounded-lg mb-6 border border-amber-200">
            <p class="text-amber-800"><span class="font-bold">⚠️ Disclaimer</span><br>This calculator is for
                educational and illustrative purposes only. It does not constitute financial, tax, or investment
                advice. Past performance is not indicative of future results. Please consult with a qualified
                financial advisor before making any investment decisions.</p>
        </div>

        <div class="text-center">
            <p class="text-xs">&copy; <?= date('Y') ?> SIP/SWP Calculator. All rights reserved.</p>
            <p class="text-xs mt-2">Built with care for global investors • <span class="text-indigo-600">No
                    tracking • No ads • Free forever</span></p>
        </div>
    </div>
</footer>