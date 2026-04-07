<?php
namespace Controllers;

use Core\ContentManager;
use Core\MetaManager;
use Core\SchemaHelper;

class CalculatorController {
    private ContentManager $contentManager;
    private MetaManager $metaManager;
    private SchemaHelper $schemaHelper;

    public function __construct() {
        $this->contentManager = new ContentManager();
        $this->metaManager = new MetaManager();
        $this->schemaHelper = new SchemaHelper();
    }
    
    public function home() {
        require_once __DIR__ . '/../Views/calculators/home.php';
    }

    public function compoundInterestCalculator() {
        $content = $this->contentManager->getParsedContent('/calculators/compound-interest-calculator');
        
        if (!$content) {
            http_response_code(404);
            echo "404 Compound Interest Guide Not Found";
            return;
        }

        $page_config = $this->metaManager->getMeta('compound-interest-calculator');
        
        // Generate breadcrumbs schema
        $breadcrumbs_schema = $this->schemaHelper->getBreadcrumbs([
            'Home' => '/',
            'Compound Interest Calculator' => '/compound-interest-calculator'
        ]);

        // Generate Article schema
        $article_schema = $this->schemaHelper->getArticle(
            $page_config['title'],
            '2026-02-27',
            '2026-02-27'
        );

        $page_config['additional_head'] = '
            <link rel="alternate" hreflang="en" href="https://sipswpcalculator.com/compound-interest-calculator">
            <link rel="alternate" hreflang="x-default" href="https://sipswpcalculator.com/compound-interest-calculator">
            <script type="application/ld+json">' . $breadcrumbs_schema . '</script>
            <script type="application/ld+json">' . $article_schema . '</script>
        ';

        $content_html = $content['html'];
        $content_metadata = $content['metadata'];
        $active_page = 'compound-interest-calculator.php';

        require_once __DIR__ . '/../Views/layouts/generic-post.php';
    }

    public function dollarCostAveragingTool() {
        $content = $this->contentManager->getParsedContent('/calculators/dollar-cost-averaging-tool');
        
        if (!$content) {
            http_response_code(404);
            echo "404 DCA Tool Guide Not Found";
            return;
        }

        $page_config = $this->metaManager->getMeta('dollar-cost-averaging-tool');
        
        $breadcrumbs_schema = $this->schemaHelper->getBreadcrumbs([
            'Home' => '/',
            'Dollar-Cost Averaging Tool' => '/dollar-cost-averaging-tool'
        ]);

        $article_schema = $this->schemaHelper->getArticle(
            $page_config['title'],
            '2026-03-02',
            '2026-03-02'
        );

        $page_config['additional_head'] = '
            <link rel="alternate" hreflang="en" href="https://sipswpcalculator.com/dollar-cost-averaging-tool">
            <link rel="alternate" hreflang="x-default" href="https://sipswpcalculator.com/dollar-cost-averaging-tool">
            <script type="application/ld+json">' . $breadcrumbs_schema . '</script>
            <script type="application/ld+json">' . $article_schema . '</script>
        ';

        $content_html = $content['html'];
        $content_metadata = $content['metadata'];
        $active_page = 'dollar-cost-averaging-tool.php';

        require_once __DIR__ . '/../Views/layouts/generic-post.php';
    }

    public function recurringInvestmentCalculator() {
        $content = $this->contentManager->getParsedContent('/calculators/recurring-investment-calculator');
        
        if (!$content) {
            http_response_code(404);
            echo "404 Recurring Investment Guide Not Found";
            return;
        }

        $page_config = $this->metaManager->getMeta('recurring-investment-calculator');
        
        $breadcrumbs_schema = $this->schemaHelper->getBreadcrumbs([
            'Home' => '/',
            'Recurring Investment Calculator' => '/recurring-investment-calculator'
        ]);

        $article_schema = $this->schemaHelper->getArticle(
            $page_config['title'],
            '2026-03-02',
            '2026-03-02'
        );

        $page_config['additional_head'] = '
            <link rel="alternate" hreflang="en" href="https://sipswpcalculator.com/recurring-investment-calculator">
            <link rel="alternate" hreflang="x-default" href="https://sipswpcalculator.com/recurring-investment-calculator">
            <script type="application/ld+json">' . $breadcrumbs_schema . '</script>
            <script type="application/ld+json">' . $article_schema . '</script>
        ';

        $content_html = $content['html'];
        $content_metadata = $content['metadata'];
        $active_page = 'recurring-investment-calculator.php';

        require_once __DIR__ . '/../Views/layouts/generic-post.php';
    }

    public function retirementDrawdownPlanner() {
        $content = $this->contentManager->getParsedContent('/calculators/retirement-drawdown-planner');
        
        if (!$content) {
            http_response_code(404);
            echo "404 Drawdown Planner Guide Not Found";
            return;
        }

        $page_config = $this->metaManager->getMeta('retirement-drawdown-planner');
        
        $breadcrumbs_schema = $this->schemaHelper->getBreadcrumbs([
            'Home' => '/',
            'Retirement Drawdown Planner' => '/retirement-drawdown-planner'
        ]);

        $article_schema = $this->schemaHelper->getArticle(
            $page_config['title'],
            '2026-03-02',
            '2026-03-02'
        );

        $page_config['additional_head'] = '
            <link rel="alternate" hreflang="en" href="https://sipswpcalculator.com/retirement-drawdown-planner">
            <link rel="alternate" hreflang="x-default" href="https://sipswpcalculator.com/retirement-drawdown-planner">
            <script type="application/ld+json">' . $breadcrumbs_schema . '</script>
            <script type="application/ld+json">' . $article_schema . '</script>
        ';

        $content_html = $content['html'];
        $content_metadata = $content['metadata'];
        $active_page = 'retirement-drawdown-planner.php';

        require_once __DIR__ . '/../Views/layouts/generic-post.php';
    }

    public function sipCalculator() {
        $content = $this->contentManager->getParsedContent('/calculators/sip-calculator');
        
        if (!$content) {
            http_response_code(404);
            echo "404 SIP Guide Not Found";
            return;
        }

        $page_config = $this->metaManager->getMeta('sip-calculator');
        
        // Generate breadcrumbs schema
        $breadcrumbs_schema = $this->schemaHelper->getBreadcrumbs([
            'Home' => '/',
            'SIP Calculator' => '/sip-calculator'
        ]);

        // Generate Article schema
        $article_schema = $this->schemaHelper->getArticle(
            $page_config['title'],
            '2026-02-25',
            '2026-02-25'
        );

        $page_config['additional_head'] = '
            <link rel="alternate" hreflang="en" href="https://sipswpcalculator.com/sip-calculator">
            <link rel="alternate" hreflang="x-default" href="https://sipswpcalculator.com/sip-calculator">
            <script type="application/ld+json">' . $breadcrumbs_schema . '</script>
            <script type="application/ld+json">' . $article_schema . '</script>
        ';

        $content_html = $content['html'];
        $content_metadata = $content['metadata'];
        $active_page = 'sip-calculator.php';

        require_once __DIR__ . '/../Views/layouts/generic-post.php';
    }

    public function sipStepUpCalculator() {
        $content = $this->contentManager->getParsedContent('/calculators/sip-step-up-calculator');
        
        if (!$content) {
            http_response_code(404);
            echo "404 Step-Up Guide Not Found";
            return;
        }

        $page_config = $this->metaManager->getMeta('sip-step-up-calculator');
        
        // Generate breadcrumbs schema
        $breadcrumbs_schema = $this->schemaHelper->getBreadcrumbs([
            'Home' => '/',
            'Step-Up SIP Guide' => '/sip-step-up-calculator'
        ]);

        // Generate Article schema
        $article_schema = $this->schemaHelper->getArticle(
            $page_config['title'],
            '2026-02-25',
            '2026-02-25'
        );

        $page_config['additional_head'] = '
            <link rel="alternate" hreflang="en" href="https://sipswpcalculator.com/sip-step-up-calculator">
            <link rel="alternate" hreflang="x-default" href="https://sipswpcalculator.com/sip-step-up-calculator">
            <script type="application/ld+json">' . $breadcrumbs_schema . '</script>
            <script type="application/ld+json">' . $article_schema . '</script>
        ';

        $content_html = $content['html'];
        $content_metadata = $content['metadata'];
        $active_page = 'sip-step-up-calculator.php';

        require_once __DIR__ . '/../Views/layouts/generic-post.php';
    }

    public function swpTaxCalculator() {
        $content = $this->contentManager->getParsedContent('/calculators/swp-tax-calculator');
        
        if (!$content) {
            http_response_code(404);
            echo "404 SWP Tax Guide Not Found";
            return;
        }

        $page_config = $this->metaManager->getMeta('swp-tax-calculator');
        
        // Generate breadcrumbs schema
        $breadcrumbs_schema = $this->schemaHelper->getBreadcrumbs([
            'Home' => '/',
            'SWP Tax Calculator' => '/swp-tax-calculator'
        ]);

        // Generate Article schema
        $article_schema = $this->schemaHelper->getArticle(
            $page_config['title'],
            '2026-02-25',
            '2026-02-25'
        );

        $page_config['additional_head'] = '
            <link rel="alternate" hreflang="en" href="https://sipswpcalculator.com/swp-tax-calculator">
            <link rel="alternate" hreflang="x-default" href="https://sipswpcalculator.com/swp-tax-calculator">
            <script type="application/ld+json">' . $breadcrumbs_schema . '</script>
            <script type="application/ld+json">' . $article_schema . '</script>
        ';

        $content_html = $content['html'];
        $content_metadata = $content['metadata'];
        $active_page = 'swp-tax-calculator.php';

        require_once __DIR__ . '/../Views/layouts/generic-post.php';
    }
}
