<?php

/**
 * calculator-form.php
 * Component for the SIP and SWP parameters inputs form.
 */

declare(strict_types=1);

?>
<div class="glass-card p-4">
    <form method="post" novalidate id="calculator-form">
        <!-- SECURITY: CSRF Token for main form -->
        <input type="hidden" name="csrf_token"
            value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8')?>">

        <!-- SECURITY: Honeypot field (hidden from real users, catches bots) -->
        <div style="position: absolute; left: -9999px; top: -9999px;" aria-hidden="true">
            <label for="website_url">Leave this field empty</label>
            <input type="text" id="website_url" name="website_url" tabindex="-1" autocomplete="off">
        </div>

        <!-- Tab Bar -->
        <div class="flex rounded-xl overflow-hidden border border-slate-200 mb-4" role="tablist">
            <button type="button" id="tab-sip" role="tab" aria-selected="true"
                onclick="switchFormTab('sip')"
                class="flex-1 flex items-center justify-center gap-1.5 py-2.5 text-xs font-bold uppercase tracking-widest transition-all duration-200 bg-emerald-500 text-white">
                <span class="flex items-center justify-center w-4 h-4 rounded-full bg-white/20 text-[9px]">1</span>
                SIP Details
            </button>
            <button type="button" id="tab-swp" role="tab" aria-selected="false"
                onclick="switchFormTab('swp')"
                class="flex-1 flex items-center justify-center gap-1.5 py-2.5 text-xs font-bold uppercase tracking-widest transition-all duration-200 bg-white text-slate-500 hover:bg-rose-50 hover:text-rose-500">
                <span class="flex items-center justify-center w-4 h-4 rounded-full bg-slate-100 text-[9px]">2</span>
                SWP Details
            </button>
        </div>

        <!-- SIP Panel -->
        <div id="panel-sip" role="tabpanel">
            <div class="relative">
                <div class="mb-4 relative z-10 bg-[var(--glass-bg)] p-4 sm:p-5 rounded-3xl border border-[var(--glass-border)] shadow-xl backdrop-blur-xl">
                    <div class="space-y-3">
                        <!-- Monthly Investment -->
                        <div class="group">
                            <label for="sip" class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">
                                Monthly SIP
                            </label>
                            <div class="relative">
                                <span class="currency-symbol absolute left-2.5 top-1/2 -translate-y-1/2 text-[10px] font-bold text-slate-500 pointer-events-none">₹</span>
                                <input type="number" id="sip" name="sip"
                                    class="w-full bg-white border border-slate-200 rounded-lg pl-6 pr-2.5 py-3 sm:py-1.5 text-sm font-bold text-emerald-600 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500/30 transition-colors"
                                    required min="500" step="500" max="1000000"
                                    value="<?= htmlspecialchars((string)$sip)?>">
                            </div>
                            <input type="range" id="sip_range" min="500" max="100000" step="500"
                                value="<?= htmlspecialchars((string)$sip)?>"
                                aria-label="Monthly SIP amount slider"
                                class="w-full h-1.5 bg-slate-200 rounded-full appearance-none cursor-pointer accent-emerald-500 mt-2">
                        </div>

                        <!-- Duration -->
                        <div class="group">
                            <label for="years" class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">
                                Period (Yrs)
                            </label>
                            <div class="relative">
                                <input type="number" id="years" name="years"
                                    class="w-full bg-white border border-slate-200 rounded-lg px-2.5 py-3 sm:py-1.5 pr-8 text-sm font-bold text-slate-700 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500/30 transition-colors"
                                    required min="1" max="50"
                                    value="<?= htmlspecialchars((string)$years)?>">
                                <span class="absolute right-2.5 top-1/2 -translate-y-1/2 text-[10px] font-bold text-slate-500 pointer-events-none">Yrs</span>
                            </div>
                            <input type="range" id="years_range" min="1" max="50" step="1"
                                value="<?= htmlspecialchars((string)$years)?>"
                                aria-label="Investment period slider"
                                class="w-full h-1.5 bg-slate-200 rounded-full appearance-none cursor-pointer accent-emerald-500 mt-2">
                        </div>

                        <!-- Expected Return -->
                        <div class="group" style="position:relative;">
                            <div class="flex items-center justify-between mb-1.5">
                                <label for="rate" class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">
                                    Expected Return
                                </label>
                                <!-- Smart Nudge trigger -->
                                <button type="button" id="rate-nudge-btn"
                                    class="inline-flex items-center gap-1 text-[10px] font-semibold text-emerald-500 hover:text-emerald-700 transition-colors focus:outline-none focus-visible:ring-1 focus-visible:ring-emerald-400 rounded"
                                    aria-haspopup="true" aria-expanded="false"
                                    aria-controls="rate-nudge-popover">
                                    <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    Not sure?
                                </button>
                            </div>
                            <div class="relative">
                                <input type="number" id="rate" step="0.5" name="rate"
                                    class="w-full bg-white border border-slate-200 rounded-lg px-2.5 py-3 sm:py-1.5 pr-6 text-sm font-bold text-slate-700 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500/30 transition-colors"
                                    required min="0" max="30"
                                    value="<?= htmlspecialchars((string)$rate)?>">
                                <span class="absolute right-2.5 top-1/2 -translate-y-1/2 text-[10px] font-bold text-slate-500 pointer-events-none">%</span>
                            </div>
                            <input type="range" id="rate_range" min="1" max="30" step="0.5"
                                value="<?= htmlspecialchars((string)$rate)?>"
                                aria-label="Expected return rate slider"
                                class="w-full h-1.5 bg-slate-200 rounded-full appearance-none cursor-pointer accent-emerald-500 mt-2">

                            <!-- Smart Nudge Popover (India-Only Localized) -->
                            <div id="rate-nudge-popover" role="dialog"
                                aria-label="Typical mutual fund returns"
                                class="hidden absolute z-50 left-0 mt-2 w-72 bg-white rounded-2xl shadow-2xl border border-slate-200 p-4 text-left"
                                style="top: 100%;">
                                <div class="flex items-center justify-between mb-3">
                                    <p class="text-xs font-extrabold text-slate-700 uppercase tracking-wider">Typical Returns (Historical)</p>
                                    <button type="button" id="rate-nudge-close"
                                        class="text-slate-400 hover:text-slate-600 transition-colors" aria-label="Close">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                </div>
                                <table class="w-full text-xs mb-3 border-separate border-spacing-0">
                                    <thead>
                                        <tr>
                                            <th class="text-left pb-1 text-slate-500 font-bold text-[10px] uppercase tracking-wide">Category</th>
                                            <th class="text-right pb-1 text-slate-500 font-bold text-[10px] uppercase tracking-wide">Historical Returns</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <tr>
                                            <td class="py-1.5 text-slate-600">Index / Large Cap</td>
                                            <td class="py-1.5 text-right font-semibold text-emerald-600">11% – 13%</td>
                                        </tr>
                                        <tr>
                                            <td class="py-1.5 text-slate-600">Flexi / Multi Cap</td>
                                            <td class="py-1.5 text-right font-semibold text-emerald-600">12% – 15%</td>
                                        </tr>
                                        <tr>
                                            <td class="py-1.5 text-slate-600">Mid / Small Cap</td>
                                            <td class="py-1.5 text-right font-semibold text-emerald-600">14% – 18%</td>
                                        </tr>
                                        <tr>
                                            <td class="py-1.5 text-slate-600">Balanced / Hybrid</td>
                                            <td class="py-1.5 text-right font-semibold text-emerald-600">9% – 12%</td>
                                        </tr>
                                    </tbody>
                                </table>
                                <p class="text-[10px] text-slate-400 mb-3 leading-relaxed">Past performance is not a guarantee of future results. 10-year historical averages.</p>
                                <div class="flex items-center gap-2">
                                    <button type="button" id="use-india-rate"
                                        class="flex-1 py-1.5 text-[11px] font-bold bg-emerald-50 text-emerald-700 rounded-lg hover:bg-emerald-100 transition-colors">
                                        Use 12% (Balanced)
                                    </button>
                                    <button type="button" id="use-us-rate"
                                        class="flex-1 py-1.5 text-[11px] font-bold bg-emerald-50 text-emerald-700 rounded-lg hover:bg-emerald-100 transition-colors">
                                        Use 15% (Growth)
                                    </button>
                                </div>
                                <a href="/resource/comparison/mf-returns-benchmarks"
                                    class="mt-3 flex items-center justify-center gap-1 text-[11px] text-indigo-500 hover:text-emerald-700 font-semibold transition-colors">
                                    Full benchmark guide →
                                </a>
                            </div>
                        </div>

                        <!-- Yearly Step-up -->
                        <div class="group">
                            <label for="stepup" class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">
                                Annual Step-up
                            </label>
                            <div class="relative">
                                <input type="number" id="stepup" step="1" name="stepup"
                                    class="w-full bg-white border border-slate-200 rounded-lg px-2.5 py-3 sm:py-1.5 pr-6 text-sm font-bold text-emerald-600 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500/30 transition-colors"
                                    required min="0" max="100"
                                    value="<?= htmlspecialchars((string)$stepup)?>">
                                <span class="absolute right-2.5 top-1/2 -translate-y-1/2 text-[10px] font-bold text-slate-500 pointer-events-none">%</span>
                            </div>
                            <input type="range" id="stepup_range" min="0" max="50" step="1"
                                value="<?= htmlspecialchars((string)$stepup)?>"
                                aria-label="Annual step-up percentage slider"
                                class="w-full h-1.5 bg-slate-200 rounded-full appearance-none cursor-pointer accent-emerald-500 mt-2">
                        </div>
                    </div>
                </div>
            </div>
        </div><!-- /panel-sip -->

        <!-- SWP Panel -->
        <div id="panel-swp" role="tabpanel" class="hidden">
            <div class="relative">
                <div class="relative z-10 bg-[var(--glass-bg)] p-4 sm:p-5 rounded-3xl border border-[var(--glass-border)] shadow-xl backdrop-blur-xl">
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-xs font-bold text-rose-400 tracking-widest uppercase">SWP Config</span>
                        <label class="toggle-switch relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" id="enable_swp" name="enable_swp"
                                onchange="toggleSwpFields()" class="sr-only peer"
                                aria-label="Enable Systematic Withdrawal Plan (SWP)">
                            <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-rose-500">
                            </div>
                        </label>
                    </div>

                    <div id="swp-fields" class="space-y-4 transition-all duration-300">
                        <!-- Monthly Withdrawal -->
                        <div class="group">
                            <label for="swp_withdrawal" class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">
                                Monthly SWP
                            </label>
                            <div class="relative">
                                <span class="currency-symbol absolute left-2.5 top-1/2 -translate-y-1/2 text-[10px] font-bold text-slate-500 pointer-events-none">₹</span>
                                <input type="number" id="swp_withdrawal" step="500" name="swp_withdrawal"
                                    class="w-full bg-white border border-slate-200 rounded-lg pl-6 pr-2.5 py-3 sm:py-1.5 text-sm font-bold text-rose-500 focus:outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400/30 transition-colors"
                                    required min="0" max="1000000"
                                    value="<?= htmlspecialchars((string)$swp_withdrawal)?>">
                            </div>
                            <input type="range" id="swp_withdrawal_range" min="1000" max="200000" step="500"
                                value="<?= htmlspecialchars((string)$swp_withdrawal)?>"
                                aria-label="Monthly SWP withdrawal slider"
                                class="w-full h-1.5 bg-slate-200 rounded-full appearance-none cursor-pointer accent-rose-500 mt-2">
                        </div>

                        <!-- Withdrawal Duration -->
                        <div class="group">
                            <label for="swp_years" class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">
                                SWP Period
                            </label>
                            <div class="relative">
                                <input type="number" id="swp_years" name="swp_years"
                                    class="w-full bg-white border border-slate-200 rounded-lg px-2.5 py-3 sm:py-1.5 pr-8 text-sm font-bold text-slate-700 focus:outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400/30 transition-colors"
                                    required min="1" max="50"
                                    value="<?= htmlspecialchars((string)$swp_years_input)?>">
                                <span class="absolute right-2.5 top-1/2 -translate-y-1/2 text-[10px] font-bold text-slate-500 pointer-events-none">Yrs</span>
                            </div>
                            <input type="range" id="swp_years_range" min="1" max="50" step="1"
                                value="<?= htmlspecialchars((string)$swp_years_input)?>"
                                aria-label="SWP period slider"
                                class="w-full h-1.5 bg-slate-200 rounded-full appearance-none cursor-pointer accent-rose-500 mt-2">
                        </div>

                        <!-- Withdrawal Hike -->
                        <div class="group">
                            <label for="swp_stepup" class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">
                                Yearly Hike
                            </label>
                            <div class="relative">
                                <input type="number" id="swp_stepup" step="0.1" name="swp_stepup"
                                    class="w-full bg-white border border-slate-200 rounded-lg px-2.5 py-3 sm:py-1.5 pr-6 text-sm font-bold text-slate-700 focus:outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400/30 transition-colors"
                                    required min="0" max="20"
                                    value="<?= htmlspecialchars((string)$swp_stepup)?>">
                                <span class="absolute right-2.5 top-1/2 -translate-y-1/2 text-[10px] font-bold text-slate-500 pointer-events-none">%</span>
                            </div>
                            <input type="range" id="swp_stepup_range" min="0" max="20" step="0.5"
                                value="<?= htmlspecialchars((string)$swp_stepup)?>"
                                aria-label="SWP yearly hike slider"
                                class="w-full h-1.5 bg-slate-200 rounded-full appearance-none cursor-pointer accent-rose-500 mt-2">
                        </div>
                    </div>
                </div>
            </div>
        </div><!-- /panel-swp -->
    </form>
</div>
