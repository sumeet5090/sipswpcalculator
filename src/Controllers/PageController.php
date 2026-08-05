<?php

declare(strict_types=1);

namespace Controllers;

use Core\BlogRepository;
use Core\FaqRepository;
use Core\GlossaryRepository;
use Core\Http\Response;
use Core\SchemaHelper;
use Core\ViewRenderer;

/**
 * PageController
 * Handles simple static page rendering using injected repositories and helpers.
 */
class PageController
{
    private FaqRepository $faqRepository;
    private BlogRepository $blogRepository;
    private GlossaryRepository $glossaryRepository;
    private SchemaHelper $schemaHelper;
    private ViewRenderer $viewRenderer;

    public function __construct(
        FaqRepository $faqRepository,
        BlogRepository $blogRepository,
        GlossaryRepository $glossaryRepository,
        SchemaHelper $schemaHelper,
        ViewRenderer $viewRenderer
    ) {
        $this->faqRepository = $faqRepository;
        $this->blogRepository = $blogRepository;
        $this->glossaryRepository = $glossaryRepository;
        $this->schemaHelper = $schemaHelper;
        $this->viewRenderer = $viewRenderer;
    }

    public function about(): Response
    {
        return Response::html($this->viewRenderer->render('pages/about'));
    }

    public function faq(): Response
    {
        $faqs = $this->faqRepository->getAll();
        $faq_categories = $this->faqRepository->getFaqCategories();

        return Response::html($this->viewRenderer->render('pages/faq', [
            'faqs' => $faqs,
            'faq_categories' => $faq_categories,
        ]));
    }

    public function glossary(): Response
    {
        $glossary_terms = $this->glossaryRepository->getAll();

        $letters = [];
        foreach ($glossary_terms as $term) {
            $firstChar = strtoupper(substr($term['q'], 0, 1));
            if (!in_array($firstChar, $letters)) {
                $letters[] = $firstChar;
            }
        }
        sort($letters);

        $breadcrumbs = $this->schemaHelper->getBreadcrumbs([
            'Home' => '/',
            'Glossary' => '/glossary'
        ]);

        $faqData = [];
        foreach ($glossary_terms as $term) {
            $faqData[$term['q']] = $term['a'];
        }
        $faq = $this->schemaHelper->getFAQ($faqData);

        return Response::html($this->viewRenderer->render('pages/glossary', [
            'glossary_terms' => $glossary_terms,
            'letters' => $letters,
            'breadcrumbs' => $breadcrumbs,
            'faq_schema' => $faq,
        ]));
    }

    public function privacy(): Response
    {
        $breadcrumbs = $this->schemaHelper->getBreadcrumbs([
            'Home' => '/',
            'Privacy Policy' => '/privacy'
        ]);

        return Response::html($this->viewRenderer->render('pages/privacy', [
            'breadcrumbs' => $breadcrumbs,
            'page_config' => [
                'title' => 'Privacy Policy',
                'robots' => 'noindex, follow'
            ]
        ]));
    }

    public function resources(): Response
    {
        $all_posts = $this->blogRepository->getAllPosts();
        $categories = $this->blogRepository->getCategories();

        $posts_by_cat = [];
        foreach ($all_posts as $post) {
            $posts_by_cat[$post['category']][] = $post;
        }

        $breadcrumbs = $this->schemaHelper->getBreadcrumbs([
            'Home' => '/',
            'Resources' => '/resources'
        ]);

        return Response::html($this->viewRenderer->render('pages/resources', [
            'categories' => $categories,
            'posts_by_cat' => $posts_by_cat,
            'breadcrumbs' => $breadcrumbs,
        ]));
    }

    public function terms(): Response
    {
        $breadcrumbs = $this->schemaHelper->getBreadcrumbs([
            'Home' => '/',
            'Terms of Service' => '/terms'
        ]);

        return Response::html($this->viewRenderer->render('pages/terms', [
            'breadcrumbs' => $breadcrumbs,
            'page_config' => [
                'title' => 'Terms of Service',
                'robots' => 'noindex, follow'
            ]
        ]));
    }
}
