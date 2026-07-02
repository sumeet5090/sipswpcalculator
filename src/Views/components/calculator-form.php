<?php

/**
 * calculator-form.php
 * Component for the SIP and SWP parameters inputs form.
 */

declare(strict_types=1);

require_once __DIR__ . '/form/input-range-pair.php';

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
                        <!-- Initial Lumpsum -->
                        <?php renderInputRangePair([
                            'id' => 'lumpsum',
                            'label' => 'Initial Lumpsum (Optional)',
                            'min' => 0,
                            'max' => 10000000,
                            'slider_max' => 1000000,
                            'step' => 5000,
                            'value' => $lumpsum ?? 0,
                            'prefix' => '₹',
                            'theme' => 'emerald'
                        ]); ?>

                        <!-- Monthly Investment -->
                        <?php renderInputRangePair([
                            'id' => 'sip',
                            'label' => 'Monthly SIP',
                            'min' => 500,
                            'max' => 1000000,
                            'slider_max' => 100000,
                            'step' => 500,
                            'value' => $sip,
                            'prefix' => '₹',
                            'theme' => 'emerald'
                        ]); ?>

                        <!-- Period (Yrs) -->
                        <?php renderInputRangePair([
                            'id' => 'years',
                            'label' => 'Period (Yrs)',
                            'min' => 1,
                            'max' => 50,
                            'step' => 1,
                            'value' => $years,
                            'suffix' => 'Yrs',
                            'theme' => 'emerald',
                            'input_text_color' => 'text-slate-700 focus:border-emerald-500 focus:ring-emerald-500/30 focus:ring-1'
                        ]); ?>

                        <!-- Expected Return -->
                        <?php 
                        ob_start();
                        ?>
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
                        <?php
                        $labelHtml = ob_get_clean();

                        renderInputRangePair([
                            'id' => 'rate',
                            'label' => 'Expected Return',
                            'label_html' => $labelHtml,
                            'min' => 0,
                            'max' => 30,
                            'step' => 0.5,
                            'value' => $rate,
                            'suffix' => '%',
                            'theme' => 'emerald',
                            'input_text_color' => 'text-slate-700 focus:border-emerald-500 focus:ring-emerald-500/30 focus:ring-1'
                        ]); 
                        ?>

                        <!-- Yearly Step-up -->
                        <?php renderInputRangePair([
                            'id' => 'stepup',
                            'label' => 'Annual Step-up',
                            'min' => 0,
                            'max' => 100,
                            'slider_max' => 50,
                            'step' => 1,
                            'value' => $stepup,
                            'suffix' => '%',
                            'theme' => 'emerald'
                        ]); ?>
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
                        <?php renderInputRangePair([
                            'id' => 'swp_withdrawal',
                            'label' => 'Monthly SWP',
                            'min' => 0,
                            'max' => 1000000,
                            'slider_max' => 200000,
                            'step' => 500,
                            'value' => $swp_withdrawal,
                            'prefix' => '₹',
                            'theme' => 'rose'
                        ]); ?>

                        <!-- Withdrawal Duration -->
                        <?php renderInputRangePair([
                            'id' => 'swp_years',
                            'label' => 'SWP Period',
                            'min' => 1,
                            'max' => 50,
                            'step' => 1,
                            'value' => $swp_years_input,
                            'suffix' => 'Yrs',
                            'theme' => 'rose',
                            'input_text_color' => 'text-slate-700 focus:border-rose-400 focus:ring-rose-400/30 focus:ring-1'
                        ]); ?>

                        <!-- Withdrawal Hike -->
                        <?php renderInputRangePair([
                            'id' => 'swp_stepup',
                            'label' => 'Yearly Hike',
                            'min' => 0,
                            'max' => 20,
                            'step' => 0.1,
                            'slider_step' => 0.5,
                            'value' => $swp_stepup,
                            'suffix' => '%',
                            'theme' => 'rose',
                            'input_text_color' => 'text-slate-700 focus:border-rose-400 focus:ring-rose-400/30 focus:ring-1'
                        ]); ?>

                        <!-- SWP Expected Return -->
                        <?php renderInputRangePair([
                            'id' => 'swp_rate',
                            'label' => 'SWP Expected Return',
                            'min' => 0.1,
                            'max' => 30,
                            'step' => 0.5,
                            'value' => $swp_rate ?? 8,
                            'suffix' => '%',
                            'theme' => 'rose'
                        ]); ?>
                    </div>
                </div>
            </div>
        </div><!-- /panel-swp -->
    </form>
</div>
