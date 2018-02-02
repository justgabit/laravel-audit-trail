<?php


namespace Mueva\AuditTrail\Tests;

use Mockery;
use Mueva\AuditTrail\AuditTrail;
use Mueva\AuditTrail\Command;
use Mueva\AuditTrail\Tests\Actions\EmptyAction;
use Mueva\AuditTrail\Tests\Actions\RegularAction;

/**
 * @coversDefaultClass \Mueva\AuditTrail\Action
 */
class ActionTest extends TestCase
{
    /**
     * @covers ::getTableName
     */
    public function test_getTableName()
    {
        $action = new EmptyAction;
        $this->assertNull($action->getTableName());
    }

    /**
     * @covers ::getTableId
     */
    public function test_getTableId()
    {
        $action = new EmptyAction;
        $this->assertNull($action->getTableId());
    }

    /**
     * @covers ::getBrief
     */
    public function test_getBrief()
    {
        $action = new EmptyAction;
        $this->assertNull($action->getBrief());
    }

    /**
     * @covers ::getEloquentModel
     */
    public function test_getEloquentModel()
    {
        $action = new EmptyAction;
        $this->assertNull($action->getEloquentModel());
    }

    /**
     * @covers ::getName
     */
    public function test_getName()
    {
        $action = new EmptyAction;
        $this->assertEquals('empty-action', $action->getName());
    }

    /**
     * @covers ::getExtras
     */
    public function test_getExtras()
    {
        $action = new EmptyAction;
        $this->assertEquals([], $action->getExtras());
    }
}
