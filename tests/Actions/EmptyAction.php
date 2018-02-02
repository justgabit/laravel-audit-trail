<?php

namespace Mueva\AuditTrail\Tests\Actions;

use Mueva\AuditTrail\Action;

class EmptyAction extends Action
{
    public function jsonSerialize(): string
    {
        return json_encode([]);
    }
}
