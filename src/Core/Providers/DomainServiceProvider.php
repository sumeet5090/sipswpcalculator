<?php

declare(strict_types=1);

namespace Core\Providers;

use Core\AdminDashboardPresenter;
use Core\BlogRepository;
use Core\Config\CalculatorRegistry;
use Core\Container;
use Core\ContentManager;
use Core\CurrencyFormatterInterface;
use Core\Factories\SchemaFactory;
use Core\FaqRepository;
use Core\InvestmentCalculator;
use Core\MetaManager;
use Core\Strategies\StrategyFactory;
use Core\ViewRenderer;
use Services\ConfigService;
use Services\GuideRenderer;
use Services\GuideSsrHydrationBuilder;
use Services\GuideViewModelBuilder;
use Services\Reports\LoanAndInflationReportHandler;
use Services\Reports\RetirementAndTaxReportHandler;
use Services\Reports\SavingsAndLoanReportHandler;
use Services\SpecializedReportBuilder;

class DomainServiceProvider implements ServiceProviderInterface
{
    /**
     * @param array<string, mixed> $config
     */
    public function register(Container $container, array $config = []): void
    {
        $container->singleton(AdminDashboardPresenter::class, function () use ($config) {
            $colorMap = $config['currencyColorMap'] ?? [];
            return new AdminDashboardPresenter($colorMap);
        });

        // Strategy Bindings across all core and specialized registered strategies
        foreach (CalculatorRegistry::getUniqueStrategyClasses() as $strategyClass) {
            if (class_exists($strategyClass) && !$container->has($strategyClass)) {
                $container->singleton($strategyClass, function (Container $c) use ($strategyClass) {
                    return new $strategyClass($c->get(ConfigService::class));
                });
            }
        }

        $container->singleton(GuideSsrHydrationBuilder::class, function (Container $c) {
            return new GuideSsrHydrationBuilder($c->get(InvestmentCalculator::class));
        });

        $container->singleton(GuideViewModelBuilder::class, function (Container $c) {
            return new GuideViewModelBuilder(
                $c->get(ContentManager::class),
                $c->get(MetaManager::class),
                $c->get(SchemaFactory::class),
                $c->get(FaqRepository::class),
                $c->get(BlogRepository::class),
                $c->get(StrategyFactory::class),
                $c->get(ConfigService::class),
                $c->get(InvestmentCalculator::class),
                $c->get(GuideSsrHydrationBuilder::class)
            );
        });

        $container->singleton(SavingsAndLoanReportHandler::class, function (Container $c) {
            return new SavingsAndLoanReportHandler($c->get(CurrencyFormatterInterface::class));
        });

        $container->singleton(LoanAndInflationReportHandler::class, function (Container $c) {
            return new LoanAndInflationReportHandler($c->get(CurrencyFormatterInterface::class));
        });

        $container->singleton(RetirementAndTaxReportHandler::class, function (Container $c) {
            return new RetirementAndTaxReportHandler($c->get(CurrencyFormatterInterface::class));
        });

        $container->singleton(SpecializedReportBuilder::class, function (Container $c) {
            return new SpecializedReportBuilder(
                $c->get(CurrencyFormatterInterface::class),
                [
                    $c->get(SavingsAndLoanReportHandler::class),
                    $c->get(LoanAndInflationReportHandler::class),
                    $c->get(RetirementAndTaxReportHandler::class),
                ]
            );
        });

        $container->singleton(GuideRenderer::class, function (Container $c) {
            return new GuideRenderer(
                $c->get(GuideViewModelBuilder::class),
                $c->get(ViewRenderer::class)
            );
        });

        $container->singleton(\Services\GuideRendererInterface::class, function (Container $c) {
            return $c->get(GuideRenderer::class);
        });
    }
}
