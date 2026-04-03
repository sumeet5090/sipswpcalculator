<?php
/**
 * layout-top.php
 * HTML head and opening tags.
 * Expects $page_config array for SEO and metadata.
 */
$page_config = $page_config ?? [];
$title = $page_config['title'] ?? 'SIP & SWP Calculator';
$meta_desc = $page_config['meta_desc'] ?? 'Free SIP & SWP calculator with step-up compounding.';
$canonical = $page_config['canonical'] ?? 'https://sipswpcalculator.com' . $_SERVER['REQUEST_URI'];
$og_title = $page_config['og_title'] ?? $title;
$og_desc = $page_config['og_desc'] ?? $meta_desc;
$og_image = $page_config['og_image'] ?? 'https://sipswpcalculator.com/assets/og-image-main.jpg';
$additional_head = $page_config['additional_head'] ?? '';
$body_class = $page_config['body_class'] ?? 'bg-gray-50 text-gray-800 font-sans antialiased';
$body_style = $page_config['body_style'] ?? 'background-image: var(--gradient-surface); background-attachment: fixed;';
require_once __DIR__ . '/important-imports.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?= htmlspecialchars($title) ?>
    </title>
    <meta name="description" content="<?= htmlspecialchars($meta_desc) ?>">
    <link rel="canonical" href="<?= htmlspecialchars($canonical) ?>">
    <meta name="robots" content="index, follow">

    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= htmlspecialchars($canonical) ?>">
    <meta property="og:title" content="<?= htmlspecialchars($og_title) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($og_desc) ?>">
    <meta property="og:image" content="<?= htmlspecialchars($og_image) ?>">
    <meta name="twitter:card" content="summary_large_image">

    <link rel="icon" type="image/svg+xml" href="/assets/favicon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@700;800&display=swap"
        rel="stylesheet">

    <link rel="stylesheet" href="/styles.css?v=<?= filemtime(__DIR__ . '/../../../styles.css') ?>">
    <link rel="stylesheet" href="/dist/tailwind.min.css?v=<?= filemtime(__DIR__ . '/../../../dist/tailwind.min.css') ?>">

    <?= $additional_head ?>

    <?php if (getenv('ENVIRONMENT') === 'production'): ?>
        <!-- Google tag (gtag.js) -->
        <script async src="https://www.googletagmanager.com/gtag/js?id=G-P5PLFTW4E7"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag() { dataLayer.push(arguments); }
            gtag('js', new Date());

            gtag('config', 'G-P5PLFTW4E7');
        </script>

        <script src="https://analytics.ahrefs.com/analytics.js" data-key="WiDGDiqV9F0xelXDCYFUfw" async></script>
    <?php endif ?>
    <script>
        console.log("<?= 'ENVIRONMENT: ' . getenv('ENVIRONMENT') ?>");
    </script>
</head>

<body class="<?= $body_class ?>" style="<?= $body_style ?>">
    <?php require_once __DIR__ . '/header.php'; ?>
    <!-- spacer to prevent content from being hidden behind fixed header -->
    <div class="h-16"></div>
    <?php $container_class = $page_config['container_class'] ?? 'max-w-7xl'; ?>
    <div class="container mt-6 mx-auto px-4 sm:px-6 lg:px-8 <?= $container_class ?>">