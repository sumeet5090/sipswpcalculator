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
use Core\Strategies\StrategyFactory;
use Core\ViewRenderer;
use Services\GuideRenderer;

class DomainServiceProvider implements ServiceProviderInterface
{
    public function register(Container $container, array $config = []): void
    {
        $container->singleton(AdminDashboardPresenter::class, function () {
            return new AdminDashboardPresenter();
        });

        $container->singleton(GuideRenderer::class, function (Container $c) {
            return new GuideRenderer(
                $c->get(ContentManager::class),
                $c->get(MetaManager::class),
                $c->get(SchemaFactory::class),
                $c->get(FaqRepository::class),
                $c->get(BlogRepository::class),
                $c->get(StrategyFactory::class),
                $c->get(ViewRenderer::class)
            );
        });
    }
}
