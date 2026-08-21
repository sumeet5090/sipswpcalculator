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
 * Backward-compatible composition controller delegating to single-responsibility actions.
 */
class PageController
{
    private RenderAboutAction $aboutAction;
    private RenderFaqAction $faqAction;
    private RenderGlossaryAction $glossaryAction;
    private RenderPrivacyAction $privacyAction;
    private RenderTermsAction $termsAction;

    public function __construct(
        FaqRepository $faqRepository,
        GlossaryRepository $glossaryRepository,
        SchemaHelper $schemaHelper,
        ViewRenderer $viewRenderer,
        MetaManager $metaManager
    ) {
        $this->aboutAction = new RenderAboutAction($metaManager, $viewRenderer);
        $this->faqAction = new RenderFaqAction($faqRepository, $viewRenderer, $metaManager);
        $this->glossaryAction = new RenderGlossaryAction($glossaryRepository, $schemaHelper, $viewRenderer, $metaManager);
        $this->privacyAction = new RenderPrivacyAction($schemaHelper, $viewRenderer, $metaManager);
        $this->termsAction = new RenderTermsAction($schemaHelper, $viewRenderer, $metaManager);
    }

    public function about(): Response
    {
        return ($this->aboutAction)();
    }

    public function faq(): Response
    {
        return ($this->faqAction)();
    }

    public function glossary(): Response
    {
        return ($this->glossaryAction)();
    }

    public function privacy(): Response
    {
        return ($this->privacyAction)();
    }

    public function terms(): Response
    {
        return ($this->termsAction)();
    }
}
