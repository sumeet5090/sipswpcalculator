<?php

declare(strict_types=1);

namespace Controllers;

use Core\Http\Response;

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
        RenderAboutAction $aboutAction,
        RenderFaqAction $faqAction,
        RenderGlossaryAction $glossaryAction,
        RenderPrivacyAction $privacyAction,
        RenderTermsAction $termsAction
    ) {
        $this->aboutAction = $aboutAction;
        $this->faqAction = $faqAction;
        $this->glossaryAction = $glossaryAction;
        $this->privacyAction = $privacyAction;
        $this->termsAction = $termsAction;
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
