<?php
// Script to safely replace hardcoded INR symbols in HTML text nodes
$files = glob("*.php");

foreach ($files as $file) {
    if (in_array($file, ['functions.php', 'sitemap.xml', 'script.js']))
        continue;

    $content = file_get_contents($file);
    $original = $content;

    // Common replacements
    $content = str_replace('₹1,00,00,000', '$1,000,000', $content);
    $content = str_replace('<span class="dynamic-amount" data-amount="10000000"></span>', '<span class="dynamic-amount" data-amount="1000000"></span>', $content);
    $content = str_replace('₹1,20,00,000', '$1,200,000', $content);
    $content = str_replace('<span class="dynamic-amount" data-amount="125000"></span>', '$1,500', $content);
    $content = str_replace('<span class="dynamic-amount" data-amount="100000"></span>', '$1,200', $content);
    $content = str_replace('₹50,000', '$500', $content);
    $content = str_replace('₹15,000', '$150', $content);
    $content = str_replace('₹10,000', '$100', $content);
    $content = str_replace('₹5,000', '$50', $content);
    $content = str_replace('₹1,000', '$10', $content);
    $content = str_replace('₹500', '$5', $content);
    $content = str_replace('₹100', '$1', $content);
    $content = str_replace('₹40,000', '$400', $content);
    $content = str_replace('₹33,333', '$333', $content);
    $content = str_replace('₹35,333', '$353', $content);
    $content = str_replace('₹37,453', '$374', $content);
    $content = str_replace('₹4,00,000', '$4,000', $content);
    $content = str_replace('₹4,24,000', '$4,240', $content);
    $content = str_replace('₹4,49,440', '$4,494', $content);
    $content = str_replace('₹58,333', '$583', $content);
    $content = str_replace('₹83,333', '$833', $content);
    $content = str_replace('<span class="dynamic-amount" data-amount="700000"></span>', '$7,000', $content);
    $content = str_replace('<span class="dynamic-amount" data-amount="210000"></span>', '$2,100', $content);
    $content = str_replace('₹2.8+ Crores', '$2.8+ Million', $content);
    $content = str_replace('<span class="dynamic-amount" data-amount="7680000"></span>', '$768,000', $content);

    // Replace remaining generic Rs / ₹
    $content = preg_replace('/₹([0-9.,]+) Lakhs?/', '$$1,000', $content); // Basic approximation
    $content = preg_replace('/₹([0-9.,]+)/', '$$1', $content);
    $content = preg_replace('/\bRs\s+([0-9.,]+)\b/', '$$1', $content);

    if ($content !== $original) {
        // file_put_contents($file, $content);
        echo "Would update $file (found changes)\n";
    }
}
?>