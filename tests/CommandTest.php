<?php


namespace Mueva\AuditTrail\Tests;

use Mockery;
use Mueva\AuditTrail\AuditTrail;
use Mueva\AuditTrail\Command;
use Mueva\AuditTrail\Tests\Actions\RegularAction;
use DB;
use Auth;

/**
 * @coversDefaultClass \Mueva\AuditTrail\Command
 */
class CommandTest extends TestCase
{
    /**
     * @covers ::getCacheKey
     */
    public function test_getCacheKey()
    {
        $command = new Command;
        $command->connection('foo')
            ->table('foo');

        $this->assertNotEmpty($command->getCacheKey());
    }

    /**
     * @covers ::getExtras
     */
    public function test_getExtras()
    {
        $command = new Command;
        $command->userId(1)
            ->table('foo')
            ->extra1('firstExtra')
            ->extra2('secondExtra');

        $this->assertEquals([
            'extra1' => 'firstExtra',
            'extra2' => 'secondExtra',
        ], $command->getExtras());
    }

    /**
     * @covers ::getConnection
     */
    public function test_get_custom_connection()
    {
        $command = new Command;
        $command->connection('test');
        $this->assertEquals('test', $command->getConnection());
    }

    /**
     * @covers ::getConnection
     */
    public function test_get_default_connection()
    {
        $command = new Command;
        $this->assertEquals(config('audit-trail.connection'), $command->getConnection());
    }

    /**
     * @covers ::table
     * @covers ::getTable
     */
    public function test_get_custom_table()
    {
        $command = new Command;
        $this->assertEquals($command, $command->table('test'));
        $this->assertEquals('test', $command->getTable());
    }

    /**
     * @covers ::getTable
     */
    public function test_get_default_table()
    {
        $command = new Command;
        $this->assertEquals(config('audit-trail.table_name'), $command->getTable());
    }

    /**
     * @covers ::action
     */
    public function test_action()
    {
        $action = new RegularAction;
        $command = new Command;
        $this->assertSame($command, $command->action($action));
        $this->assertSame($action, $command->action);
    }
    
    /**
     * @covers ::connection
     */
    public function test_connection()
    {
        $command = new Command;
        $this->assertSame($command, $command->connection('foo'));
        $this->assertEquals('foo', $command->connection);
    }

    /**
     * @covers ::userId
     */
    public function test_userId()
    {
        $command = new Command;
        $this->assertSame($command, $command->userId(123));
        $this->assertEquals(123, $command->userId);
    }

    /**
     * @covers ::resetParent
     */
    public function test_resetParent()
    {
        $command = new Command;
        $command->resetParent();
        $this->assertTrue($command->resetParent);
    }

    /**
     * @covers ::execute
     */
    public function test_execute()
    {
        $command = new Command;

        $auditTrailMock = Mockery::mock(AuditTrail::class);
        $auditTrailMock->shouldReceive('execute')->with($command);

        $this->app->instance(AuditTrail::class, $auditTrailMock);
        $command->execute();
    }

}
