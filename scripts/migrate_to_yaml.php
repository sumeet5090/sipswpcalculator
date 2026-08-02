<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Core\MetaManager;
use Core\BlogRepository;

$metaManager = new MetaManager();
$routesConfig = require __DIR__ . '/../src/Core/Config/routes.php';

// A mapping for blog redirects if we need to get their real slug
// Actually we'll just scan the directories
$contentDir = realpath(__DIR__ . '/../content');

$directoriesToScan = [
    $contentDir . '/calculators' => 'calculator',
    $contentDir . '/blog/growth' => 'blog',
    $contentDir . '/blog/retirement' => 'blog',
    $contentDir . '/blog/comparison' => 'blog',
];

// Reflect BlogRepository to get private static $postConfigs
$reflectionClass = new \ReflectionClass(BlogRepository::class);
$postConfigsProp = $reflectionClass->getProperty('postConfigs');
$postConfigsProp->setAccessible(true);
$postConfigs = $postConfigsProp->getValue();

foreach ($directoriesToScan as $dir => $type) {
    if (!is_dir($dir)) continue;

    $files = glob($dir . '/*.md');
    foreach ($files as $file) {
        $slug = basename($file, '.md');
        $rawContent = file_get_contents($file);

        // Check if already migrated
        if (str_starts_with(trim($rawContent), '---')) {
            echo "Skipping (already migrated): $slug\n";
            continue;
        }

        // Parse legacy format
        $lines = explode("\n", $rawContent);
        $title = '';
        $subtitle = '';
        $contentStartLine = 0;

        if (str_starts_with($lines[0], '# ')) {
            $title = substr($lines[0], 2);
            $contentStartLine = 1;
            if (isset($lines[1]) && trim($lines[1]) !== '' && trim($lines[1]) !== '---') {
                $subtitle = trim($lines[1]);
                $contentStartLine = 2;
            }
            if (isset($lines[$contentStartLine]) && trim($lines[$contentStartLine]) === '---') {
                $contentStartLine++;
            }
        }
        
        // Remove empty line after ---
        if (isset($lines[$contentStartLine]) && trim($lines[$contentStartLine]) === '') {
            $contentStartLine++;
        }

        $body = implode("\n", array_slice($lines, $contentStartLine));

        // Build YAML
        $yaml = "---\n";
        $yaml .= "title: \"" . addslashes(trim($title)) . "\"\n";
        if ($subtitle) {
            $yaml .= "subtitle: \"" . addslashes(trim($subtitle)) . "\"\n";
        }

        if ($type === 'calculator') {
            $metaKey = $slug; // e.g. sip-calculator
            $meta = $metaManager->getMeta($metaKey);
            $routeData = $routesConfig['calculators']['/' . $slug] ?? [];
            
            $yaml .= "meta_desc: \"" . addslashes($meta['meta_desc'] ?? '') . "\"\n";
            $yaml .= "keywords: \"" . addslashes($meta['keywords'] ?? '') . "\"\n";
            $yaml .= "canonical: \"https://sipswpcalculator.com/" . $slug . "\"\n";
            if (!empty($routeData)) {
                $yaml .= "seo_category: \"" . ($routeData['seo_category'] ?? 'growth') . "\"\n";
                $yaml .= "type: \"calculator\"\n";
                $yaml .= "date: \"" . ($routeData['date'] ?? '2026-08-01') . "\"\n";
            }
        } else {
            // Blog
            $config = $postConfigs[$slug] ?? [];
            $yaml .= "tag: \"" . ($config['tag'] ?? 'Guide') . "\"\n";
            $yaml .= "tag_color: \"" . ($config['tag_color'] ?? 'slate') . "\"\n";
            $yaml .= "featured: " . (!empty($config['featured']) ? 'true' : 'false') . "\n";
            $yaml .= "date: \"" . ($config['date'] ?? 'March 2026') . "\"\n";
            
            // Generate canonical for blogs
            $category = basename(dirname($file));
            $yaml .= "canonical: \"https://sipswpcalculator.com/resource/{$category}/{$slug}\"\n";
        }

        $yaml .= "---\n\n";

        $newContent = $yaml . ltrim($body);
        file_put_contents($file, $newContent);
        echo "Migrated: $slug\n";
    }
}
echo "Migration complete.\n";
