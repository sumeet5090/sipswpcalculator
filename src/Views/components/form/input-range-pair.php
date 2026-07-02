<?php

declare(strict_types=1);

if (!function_exists('renderInputRangePair')) {
    /**
     * Render a consistent input-range slider pair.
     */
    function renderInputRangePair(array $config): void
    {
        $id = $config['id'];
        $name = $config['name'] ?? $id;
        $label = $config['label'];
        $min = $config['min'] ?? 0;
        $max = $config['max'] ?? 100;
        $sliderMax = $config['slider_max'] ?? $max;
        $step = $config['step'] ?? 1;
        $sliderStep = $config['slider_step'] ?? $step;
        $value = $config['value'] ?? 0;
        $prefix = $config['prefix'] ?? null;
        $suffix = $config['suffix'] ?? null;
        
        // Theme selection: emerald (SIP) or rose (SWP)
        $theme = $config['theme'] ?? 'emerald';
        
        $inputColorClass = ($theme === 'rose') 
            ? 'text-rose-500 focus:border-rose-400 focus:ring-rose-400/30 focus:ring-1' 
            : 'text-emerald-600 focus:border-emerald-500 focus:ring-emerald-500/30 focus:ring-1';
            
        $rangeColorClass = ($theme === 'rose') ? 'accent-rose-500' : 'accent-emerald-500';
        $textClass = $config['input_text_color'] ?? $inputColorClass;
        $labelHtml = $config['label_html'] ?? null;
        ?>
        <div class="group">
            <?php if ($labelHtml): ?>
                <?= $labelHtml ?>
            <?php else: ?>
                <label for="<?= htmlspecialchars($id) ?>" class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5 block">
                    <?= htmlspecialchars($label) ?>
                </label>
            <?php endif; ?>
            <div class="relative">
                <?php if ($prefix): ?>
                    <span class="currency-symbol absolute left-2.5 top-1/2 -translate-y-1/2 text-[10px] font-bold text-slate-500 pointer-events-none"><?= htmlspecialchars($prefix) ?></span>
                <?php endif; ?>
                <input type="number" id="<?= htmlspecialchars($id) ?>" name="<?= htmlspecialchars($name) ?>"
                    class="w-full bg-white border border-slate-200 rounded-lg <?= $prefix ? 'pl-6' : 'px-2.5' ?> <?= $suffix ? 'pr-8' : 'pr-2.5' ?> py-3 sm:py-1.5 text-sm font-bold <?= $textClass ?> focus:outline-none transition-colors"
                    required min="<?= htmlspecialchars((string)$min) ?>" step="<?= htmlspecialchars((string)$step) ?>" max="<?= htmlspecialchars((string)$max) ?>"
                    value="<?= htmlspecialchars((string)$value) ?>">
                <?php if ($suffix): ?>
                    <span class="absolute right-2.5 top-1/2 -translate-y-1/2 text-[10px] font-bold text-slate-500 pointer-events-none"><?= htmlspecialchars($suffix) ?></span>
                <?php endif; ?>
            </div>
            <input type="range" id="<?= htmlspecialchars($id) ?>_range" min="<?= htmlspecialchars((string)$min) ?>" max="<?= htmlspecialchars((string)$sliderMax) ?>" step="<?= htmlspecialchars((string)$sliderStep) ?>"
                value="<?= htmlspecialchars((string)$value) ?>"
                aria-label="<?= htmlspecialchars($label) ?> slider"
                class="w-full h-1.5 bg-slate-200 rounded-full appearance-none cursor-pointer <?= $rangeColorClass ?> mt-2">
        </div>
        <?php
    }
}
