<?php
// finalize_replace.php
// Script to replace hardcoded INR symbols and Indian terminology with generic US equivalents

$files = glob("*.php");

foreach ($files as $file) {
    if (in_array($file, ['functions.php', 'sitemap.xml', 'script.js', 'test_replace.php', 'finalize_replace.php', 'navbar.php', 'footer.php']))
        continue;

    $content = file_get_contents($file);
    $original = $content;

    // 1. Specific precise string replacements for exact context matches
    $replacements = [
        '₹1,00,00,000' => '$1,000,000',
        '₹1,20,00,000' => '$1,200,000',
        '₹1 Crore' => '$1 Million',
        'Rs 1 Crore' => '$1 Million',
        '₹2.8+ Crores' => '$2.8+ Million',
        '₹1.25 Lakh' => '$1,500',
        'Rs 1.25 Lakh' => '$1,500',
        '₹1 Lakh' => '$1,200',
        'Rs 1 Lakh' => '$1,200',
        '₹76.8 Lakhs' => '$768,000',
        '₹21,000 Crore' => '$2.5 Billion',
        '₹10,00,000' => '$10,000',
        '₹4,49,440' => '$4,494',
        '₹4,24,000' => '$4,240',
        '₹4,00,000' => '$4,000',
        '₹1,00,000' => '$1,000',
        '₹83,333' => '$833',
        '₹58,333' => '$583',
        '₹50,000' => '$500',
        'Rs 50,000' => '$500',
        '₹40,000' => '$400',
        'Rs 40,000' => '$400',
        '₹37,453' => '$374',
        '₹35,333' => '$353',
        '₹33,333' => '$333',
        'Rs 33,333' => '$333',
        '₹31,180' => '$311',
        'Rs 31,180' => '$311',
        '₹15,000' => '$150',
        '₹10,000' => '$100',
        'Rs 10,000' => '$100',
        '₹5,000' => '$50',
        '₹1,000' => '$10',
        '₹500' => '$5',
        'Rs 500' => '$5',
        '₹100' => '$1',
        'Rs 100' => '$1',
        '₹7 Lakh' => '$7,000',
        '₹2.1 Lakh' => '$2,100',
        '₹3.54 Crore' => '$3.54 Million',
        'Rs 3.54 Crore' => '$3.54 Million',
        '₹1.73 Crore' => '$1.73 Million',
        'Rs 1.73 Crore' => '$1.73 Million',
        '₹5.7 Crore' => '$5.7 Million',
        'Rs 5.7 Crore' => '$5.7 Million',
        '₹5+ Lakhs' => '$5,000+',
        'Rs 5 Lakh' => '$5,000',
        '₹5 Lakh' => '$5,000',
        '₹17 Lakh' => '$17,000',
        'Rs 17 Lakh' => '$17,000',
        'Rs 17.3 Lakh' => '$17,300',
        'Rs 23.2 Lakh' => '$23,200',
        '₹31,000' => '$310',
        '₹4 Lakh' => '$4,000',
        '₹4.24 Lakhs' => '$4,240',
        'Rs 55,839' => '$558',
        'Rs 40,000' => '$400',
        'Rs 4.8 Lakh' => '$4,800',
        'Rs 10 Lakh' => '$10,000'
    ];

    $content = str_replace(array_keys($replacements), array_values($replacements), $content);

    // 2. Catch remaining floating ₹xxx,xxx using generic regex, 
    // replacing the comma style if needed.
    // e.g. ₹5,00,000 -> $5,000 (We'll just strip ₹ for $ and let commas be for now)
    $content = preg_replace('/₹([0-9.,]+)/', '$$1', $content);
    $content = preg_replace('/\bRs\s+([0-9.,]+)\b/', '$$1', $content);

    // 3. Address language specific to India (where appropriate)
    $content = str_replace('Indian Retirees', 'Global Retirees', $content);
    $content = str_replace('Indian context', 'global context', $content);
    $content = str_replace('in India', 'worldwide', $content);
    $content = str_replace('Indian inflation', 'global inflation', $content);

    if ($content !== $original) {
        file_put_contents($file, $content);
        echo "Updated $file\n";
    }
}
?>