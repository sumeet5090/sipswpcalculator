<?php
declare(strict_types=1);

$heroBadge = $hero['badge'] ?? '';
$heroTitlePrefix = $hero['title_prefix'] ?? '';
$heroTitleHighlight = $hero['title_highlight'] ?? '';
$heroTitleSuffix = $hero['title_suffix'] ?? '';
$heroDesc = $hero['description'] ?? '';
$heroExtraClass = $hero['extra_class'] ?? '';
?>
<header class="text-center mb-14 md:mb-20 pt-6 <?= htmlspecialchars($heroExtraClass) ?>">
    <?php if ($heroBadge): ?>
        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-100/60 uppercase tracking-widest mb-4">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
            <?= htmlspecialchars($heroBadge) ?>
        </span>
    <?php endif; ?>
    <h1 class="text-4xl sm:text-5xl md:text-6xl font-extrabold tracking-tight pb-3">
        <?= htmlspecialchars($heroTitlePrefix) ?>
        <?php if ($heroTitleHighlight): ?>
            <span class="bg-gradient-to-r from-emerald-600 to-teal-500 bg-clip-text text-transparent"><?= htmlspecialchars($heroTitleHighlight) ?></span>
        <?php endif; ?>
        <?= htmlspecialchars($heroTitleSuffix) ?>
    </h1>
    <?php if ($heroDesc): ?>
        <p class="mt-4 text-lg text-slate-500 max-w-2xl mx-auto leading-relaxed font-medium">
            <?= htmlspecialchars($heroDesc) ?>
        </p>
    <?php endif; ?>
</header>
