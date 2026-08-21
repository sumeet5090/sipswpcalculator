<?php

declare(strict_types=1);

namespace Core\Strategies;

class TargetCorpusStrategy extends BaseStrategy
{
    public function getType(): string
    {
        return 'target_corpus';
    }
}
