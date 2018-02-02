<?php

namespace Mueva\AuditTrail\Tests\Actions;

use Mueva\AuditTrail\Action;
use Mueva\AuditTrail\Tests\Models\Foo;

class ExtraFieldsAction extends Action
{
    public function jsonSerialize(): string
    {
        return json_encode([]);
    }

    public function getTableName()
    {
        return 'extra';
    }

    public function getTableId()
    {
        return 123;
    }

    public function getBrief()
    {
        return 'Action with extra fields';
    }

    public function getExtras(): array
    {
        return [
            'extra1' => 'foo',
            'extra2' => 'bar',
        ];
    }
}
