<?php

declare(strict_types=1);

namespace Controllers;

use Core\FaqRepository;
use Core\GlossaryRepository;
use Core\Http\Response;
use Core\MetaManager;
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
    private MetaManager $metaManager;

    public function __construct(
        FaqRepository $faqRepository,
        GlossaryRepository $glossaryRepository,
        SchemaHelper $schemaHelper,
        ViewRenderer $viewRenderer,
        MetaManager $metaManager
    ) {
        $this->faqRepository = $faqRepository;
        $this->glossaryRepository = $glossaryRepository;
        $this->schemaHelper = $schemaHelper;
        $this->viewRenderer = $viewRenderer;
        $this->metaManager = $metaManager;
    }

    public function about(): Response
    {
        $page_config = $this->metaManager->getMeta('about');

        return Response::html($this->viewRenderer->render('pages/about', [
            'page_config' => $page_config,
        ]));
    }

    public function faq(): Response
    {
        $faqs = $this->faqRepository->getAll();
        $faq_categories = $this->faqRepository->getFaqCategories();
        $page_config = $this->metaManager->getMeta('faq');

        return Response::html($this->viewRenderer->render('pages/faq', [
            'faqs' => $faqs,
            'faq_categories' => $faq_categories,
            'page_config' => $page_config,
        ]));
    }

    public function glossary(): Response
    {
        $glossary_terms = $this->glossaryRepository->getAll();
        $letters = $this->glossaryRepository->getAlphabeticalLetters();
        $page_config = $this->metaManager->getMeta('glossary');

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
            'page_config' => $page_config,
        ]));
    }

    public function privacy(): Response
    {
        $page_config = $this->metaManager->getMeta('privacy');

        $breadcrumbs = $this->schemaHelper->getBreadcrumbs([
            'Home' => '/',
            'Privacy Policy' => '/privacy'
        ]);

        return Response::html($this->viewRenderer->render('pages/privacy', [
            'breadcrumbs' => $breadcrumbs,
            'page_config' => $page_config,
        ]));
    }

    public function terms(): Response
    {
        $page_config = $this->metaManager->getMeta('terms');

        $breadcrumbs = $this->schemaHelper->getBreadcrumbs([
            'Home' => '/',
            'Terms of Service' => '/terms'
        ]);

        return Response::html($this->viewRenderer->render('pages/terms', [
            'breadcrumbs' => $breadcrumbs,
            'page_config' => $page_config,
        ]));
    }
}
