<?php

declare(strict_types=1);

namespace Core\Providers;

use Core\AnonymizedInsightLogger;
use Core\BlogRepository;
use Core\Container;
use Core\ContentManager;
use Core\Factories\SchemaFactory;
use Core\FaqRepository;
use Core\GlossaryRepository;
use Core\InsightRepository;
use Core\MetaManager;
use Core\SchemaHelper;
use Core\SiteConfig;
use PDO;

class RepositoryServiceProvider implements ServiceProviderInterface
{
    public function register(Container $container, array $config = []): void
    {
        $container->singleton(MetaManager::class, function (Container $c) {
            return new MetaManager($c->get(SiteConfig::class));
        });

        $container->singleton(SchemaHelper::class, function (Container $c) {
            return new SchemaHelper($c->get(SiteConfig::class));
        });

        $container->singleton(FaqRepository::class, function () {
            return new FaqRepository(__DIR__ . '/../../../content/faqs.json');
        });

        $container->singleton(GlossaryRepository::class, function () {
            return new GlossaryRepository(__DIR__ . '/../../../content/glossary.json');
        });

        $container->singleton(BlogRepository::class, function (Container $c) {
            return new BlogRepository($c->get(ContentManager::class));
        });

        $container->singleton(InsightRepository::class, function (Container $c) {
            /** @var \Services\ConfigService $configService */
            $configService = $c->get(\Services\ConfigService::class);
            $bucketConfig = $configService->getJsonConfig('content/dashboard_buckets.json');
            return new InsightRepository($c->get(PDO::class), $bucketConfig);
        });

        $container->singleton(AnonymizedInsightLogger::class, function (Container $c) {
            return new AnonymizedInsightLogger($c->get(PDO::class));
        });

        $container->singleton(SchemaFactory::class, function (Container $c) {
            return new SchemaFactory(
                $c->get(SchemaHelper::class),
                $c->get(SiteConfig::class),
                $c->get(BlogRepository::class),
                $c->get(ContentManager::class)
            );
        });
    }
}
