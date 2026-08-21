<?php

declare(strict_types=1);

namespace Core\Providers;

use Core\AdminDashboardPresenter;
use Core\BlogRepository;
use Core\Container;
use Core\ContentManager;
use Core\Factories\SchemaFactory;
use Core\FaqRepository;
use Core\MetaManager;
use Core\Strategies\ComboStrategy;
use Core\Strategies\LumpsumStrategy;
use Core\Strategies\SipStrategy;
use Core\Strategies\StrategyFactory;
use Core\Strategies\SwpStrategy;
use Core\Strategies\TargetCorpusStrategy;
use Core\ViewRenderer;
use Services\ConfigService;
use Services\GuideRenderer;
use Services\GuideViewModelBuilder;

class DomainServiceProvider implements ServiceProviderInterface
{
    public function register(Container $container, array $config = []): void
    {
        $container->singleton(AdminDashboardPresenter::class, function () use ($config) {
            $colorMap = $config['currencyColorMap'] ?? [];
            return new AdminDashboardPresenter($colorMap);
        });

        // Strategy Bindings
        $container->singleton(SipStrategy::class, function (Container $c) {
            return new SipStrategy($c->get(ConfigService::class));
        });

        $container->singleton(SwpStrategy::class, function (Container $c) {
            return new SwpStrategy($c->get(ConfigService::class));
        });

        $container->singleton(LumpsumStrategy::class, function (Container $c) {
            return new LumpsumStrategy($c->get(ConfigService::class));
        });

        $container->singleton(TargetCorpusStrategy::class, function (Container $c) {
            return new TargetCorpusStrategy($c->get(ConfigService::class));
        });

        $container->singleton(ComboStrategy::class, function (Container $c) {
            return new ComboStrategy($c->get(ConfigService::class));
        });

        $container->singleton(GuideViewModelBuilder::class, function (Container $c) {
            return new GuideViewModelBuilder(
                $c->get(ContentManager::class),
                $c->get(MetaManager::class),
                $c->get(SchemaFactory::class),
                $c->get(FaqRepository::class),
                $c->get(BlogRepository::class),
                $c->get(StrategyFactory::class),
                $c->get(ConfigService::class)
            );
        });

        $container->singleton(GuideRenderer::class, function (Container $c) {
            return new GuideRenderer(
                $c->get(GuideViewModelBuilder::class),
                $c->get(ViewRenderer::class)
            );
        });
    }
}
