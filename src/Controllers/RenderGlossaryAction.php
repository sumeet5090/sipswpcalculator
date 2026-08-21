<?php

declare(strict_types=1);

namespace Controllers;

use Core\GlossaryRepository;
use Core\Http\Request;
use Core\Http\Response;
use Core\MetaManager;
use Core\SchemaHelper;
use Core\ViewRenderer;

/**
 * RenderGlossaryAction
 * Single Responsibility action dedicated strictly to rendering the Glossary page.
 */
class RenderGlossaryAction
{
    private GlossaryRepository $glossaryRepository;
    private SchemaHelper $schemaHelper;
    private ViewRenderer $viewRenderer;
    private MetaManager $metaManager;

    public function __construct(
        GlossaryRepository $glossaryRepository,
        SchemaHelper $schemaHelper,
        ViewRenderer $viewRenderer,
        MetaManager $metaManager
    ) {
        $this->glossaryRepository = $glossaryRepository;
        $this->schemaHelper = $schemaHelper;
        $this->viewRenderer = $viewRenderer;
        $this->metaManager = $metaManager;
    }

    public function __invoke(?Request $request = null): Response
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
            'active_page' => 'glossary',
        ]));
    }
}
