<?php

declare(strict_types=1);

namespace Controllers;

use Core\FaqRepository;
use Core\Http\Request;
use Core\Http\Response;
use Core\MetaManager;
use Core\ViewRenderer;

/**
 * RenderFaqAction
 * Single Responsibility action dedicated strictly to rendering the FAQ page.
 */
class RenderFaqAction
{
    private FaqRepository $faqRepository;
    private ViewRenderer $viewRenderer;
    private MetaManager $metaManager;

    public function __construct(
        FaqRepository $faqRepository,
        ViewRenderer $viewRenderer,
        MetaManager $metaManager
    ) {
        $this->faqRepository = $faqRepository;
        $this->viewRenderer = $viewRenderer;
        $this->metaManager = $metaManager;
    }

    public function __invoke(?Request $request = null): Response
    {
        $faqs = $this->faqRepository->getAll();
        $faq_categories = $this->faqRepository->getFaqCategories();
        $page_config = $this->metaManager->getMeta('faq');

        return Response::html($this->viewRenderer->render('pages/faq', [
            'faqs' => $faqs,
            'faq_categories' => $faq_categories,
            'page_config' => $page_config,
            'active_page' => 'faq',
        ]));
    }
}
