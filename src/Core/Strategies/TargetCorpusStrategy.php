<?php

declare(strict_types=1);

namespace Core\Strategies;

class TargetCorpusStrategy extends BaseStrategy
{
    public function getType(): string
    {
        return 'target_corpus';
    }

    public function getBenchmarkTemplate(): string
    {
        return 'components/benchmarks/target-corpus.twig';
    }

    public function getBenchmarkTitle(): string
    {
        return 'Target Corpus Goal-Seek Matrix: Required Monthly SIP (12% CAGR)';
    }
}
