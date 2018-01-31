<?php

namespace Mueva\AuditTrail\Tests\Actions;

use Mueva\AuditTrail\Action;

class EmptyAction extends Action
{
    public function jsonSerialize()
    {
        return json_encode([]);
    }
}
