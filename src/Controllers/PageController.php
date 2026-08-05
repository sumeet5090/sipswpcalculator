<?php

declare(strict_types=1);

namespace Controllers;

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
    private GlossaryRepository $glossaryRepository;
    private SchemaHelper $schemaHelper;
    private ViewRenderer $viewRenderer;

    public function __construct(
        FaqRepository $faqRepository,
        GlossaryRepository $glossaryRepository,
        SchemaHelper $schemaHelper,
        ViewRenderer $viewRenderer
    ) {
        $this->faqRepository = $faqRepository;
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
        $letters = $this->glossaryRepository->getAlphabeticalLetters();

        $breadcrumbs = $this->schemaHelper->getBreadcrumbs([
            'Home' => '/',
            'Glossary' => '/glossary'
        ]);

        $faq = $this->schemaHelper->getFAQ($this->glossaryRepository->toFaqSchemaData());

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
