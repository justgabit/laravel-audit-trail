<?php

namespace Mueva\AuditTrail\Tests\Actions;

use Mueva\AuditTrail\Action;
use Mueva\AuditTrail\Tests\Models\Foo;

class RegularAction extends Action
{
    public function jsonSerialize(): string
    {
        return json_encode([]);
    }

    public function getTableName()
    {
        return 'regular';
    }

    public function getTableId()
    {
        return 99;
    }

    public function getBrief()
    {
        return 'Regular action';
    }
}
