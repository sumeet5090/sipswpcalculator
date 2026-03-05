<?php
// Script to safely replace hardcoded INR symbols in HTML text nodes
$files = glob("*.php");

foreach ($files as $file) {
    if (in_array($file, ['functions.php', 'sitemap.xml', 'script.js']))
        continue;

    $content = file_get_contents($file);
    $original = $content;

    // Common replacements
    $content = str_replace('â‚¹1,00,00,000', '$1,000,000', $content);
    $content = str_replace('â‚¹1 Crore', '$1 Million', $content);
    $content = str_replace('â‚¹1,20,00,000', '$1,200,000', $content);
    $content = str_replace('â‚¹1.25 Lakh', '$1,500', $content);
    $content = str_replace('â‚¹1 Lakh', '$1,200', $content);
    $content = str_replace('â‚¹50,000', '$500', $content);
    $content = str_replace('â‚¹15,000', '$150', $content);
    $content = str_replace('â‚¹10,000', '$100', $content);
    $content = str_replace('â‚¹5,000', '$50', $content);
    $content = str_replace('â‚¹1,000', '$10', $content);
    $content = str_replace('â‚¹500', '$5', $content);
    $content = str_replace('â‚¹100', '$1', $content);
    $content = str_replace('â‚¹40,000', '$400', $content);
    $content = str_replace('â‚¹33,333', '$333', $content);
    $content = str_replace('â‚¹35,333', '$353', $content);
    $content = str_replace('â‚¹37,453', '$374', $content);
    $content = str_replace('â‚¹4,00,000', '$4,000', $content);
    $content = str_replace('â‚¹4,24,000', '$4,240', $content);
    $content = str_replace('â‚¹4,49,440', '$4,494', $content);
    $content = str_replace('â‚¹58,333', '$583', $content);
    $content = str_replace('â‚¹83,333', '$833', $content);
    $content = str_replace('â‚¹7 Lakh', '$7,000', $content);
    $content = str_replace('â‚¹2.1 Lakh', '$2,100', $content);
    $content = str_replace('â‚¹2.8+ Crores', '$2.8+ Million', $content);
    $content = str_replace('â‚¹76.8 Lakhs', '$768,000', $content);

    // Replace remaining generic Rs / â‚¹
    $content = preg_replace('/â‚¹([0-9.,]+) Lakhs?/', '$$1,000', $content); // Basic approximation
    $content = preg_replace('/â‚¹([0-9.,]+)/', '$$1', $content);
    $content = preg_replace('/\bRs\s+([0-9.,]+)\b/', '$$1', $content);

    if ($content !== $original) {
        // file_put_contents($file, $content);
        echo "Would update $file (found changes)\n";
    }
}
?>