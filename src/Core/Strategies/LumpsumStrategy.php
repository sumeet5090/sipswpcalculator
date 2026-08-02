<?php

declare(strict_types=1);

namespace Core\Strategies;

class LumpsumStrategy extends BaseStrategy
{
    public function getType(): string
    {
        return 'lumpsum';
    }
}
