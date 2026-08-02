<?php

declare(strict_types=1);

namespace Core\Strategies;

class SipStrategy extends BaseStrategy
{
    public function getType(): string
    {
        return 'sip';
    }
}
