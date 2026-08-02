<?php

declare(strict_types=1);

namespace Controllers;

use Core\View;

/**
 * PageController
 * Handles simple static page rendering.
 */
class PageController
{
    public function about(): void
    {
        View::render('pages/about');
    }

    public function faq(): void
    {
        $faqRepository = new \Core\FaqRepository();
        $faqs = $faqRepository->getAll();

        $faq_categories = [
            ['id' => 'basics', 'label' => 'Basics', 'icon' => 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
            ['id' => 'strategies', 'label' => 'Strategies', 'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
            ['id' => 'tax', 'label' => 'Tax & Risk', 'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M12 16V7'],
            ['id' => 'selection', 'label' => 'Selection', 'icon' => 'M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z']
        ];

        View::render('pages/faq', [
            'faqs' => $faqs,
            'faq_categories' => $faq_categories,
        ]);
    }

    public function glossary(): void
    {
        $jsonPath = __DIR__ . '/../../content/glossary.json';
        $glossary_terms = [];

        if (file_exists($jsonPath)) {
            $jsonContent = file_get_contents($jsonPath);
            $decoded = json_decode($jsonContent, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $glossary_terms = $decoded;
            } else {
                error_log("Failed to parse content/glossary.json: " . json_last_error_msg());
            }
        }

        usort($glossary_terms, function ($a, $b) {
            return strcmp($a['q'], $b['q']);
        });

        $letters = [];
        foreach ($glossary_terms as $term) {
            $firstChar = strtoupper(substr($term['q'], 0, 1));
            if (!in_array($firstChar, $letters)) {
                $letters[] = $firstChar;
            }
        }
        sort($letters);

        $schemaHelper = new \Core\SchemaHelper();
        $breadcrumbs = $schemaHelper->getBreadcrumbs([
            'Home' => '/',
            'Glossary' => '/glossary'
        ]);

        $faqData = [];
        foreach ($glossary_terms as $term) {
            $faqData[$term['q']] = $term['a'];
        }
        $faq = $schemaHelper->getFAQ($faqData);

        View::render('pages/glossary', [
            'glossary_terms' => $glossary_terms,
            'letters' => $letters,
            'breadcrumbs' => $breadcrumbs,
            'faq_schema' => $faq,
        ]);
    }

    public function privacy(): void
    {
        $schemaHelper = new \Core\SchemaHelper();
        $breadcrumbs = $schemaHelper->getBreadcrumbs([
            'Home' => '/',
            'Privacy Policy' => '/privacy'
        ]);

        View::render('pages/privacy', [
            'breadcrumbs' => $breadcrumbs,
            'page_config' => [
                'title' => 'Privacy Policy',
                'robots' => 'noindex, follow'
            ]
        ]);
    }

    public function resources(): void
    {
        $all_posts = \Core\BlogRepository::getAllPosts();
        $categories = \Core\BlogRepository::getCategories();

        $posts_by_cat = [];
        foreach ($all_posts as $post) {
            $posts_by_cat[$post['category']][] = $post;
        }

        $schemaHelper = new \Core\SchemaHelper();
        $breadcrumbs = $schemaHelper->getBreadcrumbs([
            'Home' => '/',
            'Resources' => '/resources'
        ]);

        View::render('pages/resources', [
            'categories' => $categories,
            'posts_by_cat' => $posts_by_cat,
            'breadcrumbs' => $breadcrumbs,
        ]);
    }

    public function terms(): void
    {
        $schemaHelper = new \Core\SchemaHelper();
        $breadcrumbs = $schemaHelper->getBreadcrumbs([
            'Home' => '/',
            'Terms of Service' => '/terms'
        ]);

        View::render('pages/terms', [
            'breadcrumbs' => $breadcrumbs,
            'page_config' => [
                'title' => 'Terms of Service',
                'robots' => 'noindex, follow'
            ]
        ]);
    }
}
