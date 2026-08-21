<?php

declare(strict_types=1);

namespace Core\Strategies;

class ComboStrategy extends BaseStrategy
{
    public function getType(): string
    {
        return 'combo';
    }
}
