<?php


namespace Mueva\AuditTrail\Tests;

use DB;
use Auth;
use Cache;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Mueva\AuditTrail\AuditTrail;
use Mueva\AuditTrail\Tests\Actions\EloquentAction;
use Mueva\AuditTrail\Tests\Actions\ExtraFieldsAction;
use Mueva\AuditTrail\Tests\Actions\RegularAction;
use Mueva\AuditTrail\Tests\Models\Foo;
use Mueva\AuditTrail\Tests\Models\User;

/**
 * @coversDefaultClass \Mueva\AuditTrail\AuditTrail
 */
class AuditTrailTest extends TestCase
{
    /**
     * @covers \Mueva\AuditTrail\AuditTrailServiceProvider::register
     */
    public function test_audit_trail_is_a_singleton()
    {
        $this->assertSame(resolve(AuditTrail::class), resolve(AuditTrail::class));
    }

    /**
     * @covers \Mueva\AuditTrail\AuditTrailServiceProvider::boot
     * @covers ::create
     * @covers ::execute
     */
    public function test_eloquent_model_is_properly_saved()
    {
        config(['audit-trail.encrypt_action_data' => false]);

        $foo = new Foo;
        $foo->id = 5;
        $foo->bar = 'baz';

        $action = new EloquentAction($foo);
        $userId = 20;

        AuditTrail::create()
            ->action($action)
            ->userId($userId)
            ->execute();

        $result = DB::table('audit_trail')->get();
        $this->assertCount(1, $result);
        $result = $result->first();

        $this->assertJson($result->action_data);
        $this->assertEquals('Eloquent action with id 5', $result->action_brief);
        $this->assertEquals('foo', $result->table_name);
        $this->assertEquals(5, $result->table_id);
        $this->assertEquals($action->getName(), $result->action_id);
        $this->assertEquals($userId, $result->user_id);
        $this->assertTrue($this->dateIsCurrent($result->date));
        $this->assertNull($result->parent_id);
    }

    /**
     * @covers \Mueva\AuditTrail\AuditTrailServiceProvider::boot
     * @covers ::execute
     */
    public function test_eloquent_models_without_primary_keys_save_null_in_table_field()
    {
        $action = new EloquentAction(new Foo);
        $userId = 20;

        AuditTrail::create()
            ->action($action)
            ->userId($userId)
            ->execute();

        $result = DB::table('audit_trail')->get();
        $this->assertCount(1, $result);
        $result = $result->first();

        $this->assertNull($result->table_id);
    }

    /**
     * @covers ::execute
     */
    public function test_encrypt_action_data()
    {
        config(['audit-trail.encrypt_action_data' => true]);

        AuditTrail::create()
            ->action(new RegularAction)
            ->userId(123)
            ->execute();

        $result = DB::table('audit_trail')->first();

        $this->assertEquals('[]', decrypt($result->action_data));
    }

    /**
     * @covers ::execute
     */
    public function test_explicit_extra_fields_are_saved()
    {
        $this->createExtraFields(['extra1', 'extra2']);

        AuditTrail::create()
            ->action(new RegularAction)
            ->userId(123)
            ->extra1('foo')
            ->extra2('bar')
            ->execute();

        $result = DB::table('audit_trail')->first();
        $this->assertEquals('foo', $result->extra1);
        $this->assertEquals('bar', $result->extra2);
    }

    /**
     * @covers ::execute
     */
    public function test_extra_fields_are_saved_when_action_provides_them()
    {
        $this->createExtraFields(['extra1', 'extra2']);

        AuditTrail::create()
            ->action(new ExtraFieldsAction)
            ->userId(123)
            ->execute();

        $result = DB::table('audit_trail')->first();
        $this->assertEquals('foo', $result->extra1);
        $this->assertEquals('bar', $result->extra2);
    }

    /**
     * @covers ::execute
     * @covers ::autodetectUserid
     */
    public function test_autodetect_user_id()
    {
        $userId = 4;
        $this->setupUsers();
        $this->loginUser($userId);

        $foo = new Foo;
        $foo->id = 5;

        $action = new EloquentAction($foo);

        AuditTrail::create()
            ->action($action)
            ->execute();

        $result = DB::table('audit_trail')->get();
        $this->assertCount(1, $result);
        $result = $result->first();
        $this->assertEquals($userId, $result->user_id);
    }

    /**
     * @covers ::execute
     * @covers ::autodetectUserid
     * @expectedException \Mueva\AuditTrail\Exceptions\AutodetectUserIdException
     */
    public function test_autodetect_user_id_throws_exception()
    {
        AuditTrail::create()
            ->action(new RegularAction)
            ->execute();
    }

    /**
     * @covers ::execute
     */
    public function test_non_eloquent_actions_set_table_name_and_table_id()
    {
        $action = new RegularAction;
        AuditTrail::create()
            ->action($action)
            ->userId(1)
            ->execute();

        $result = DB::table('audit_trail')->get();
        $this->assertCount(1, $result);
        $result = $result->first();
        $this->assertEquals($action->getTableName(), $result->table_name);
        $this->assertEquals($action->getTableId(), $result->table_id);
    }

    /**
     * @covers ::execute
     */
    public function test_parent_id_is_set_in_consecutive_calls()
    {
        $action = new RegularAction;
        AuditTrail::create()->action($action)->userId(1)->execute();
        AuditTrail::create()->action($action)->userId(1)->execute();
        AuditTrail::create()->action($action)->userId(1)->execute();

        $results = DB::table('audit_trail')->get();
        $this->assertCount(3, $results);

        foreach ($results as $result) {
            if ($result->id == 1) {
                // First row has no parent
                $this->assertNull($result->parent_id);
            } else {
                // All remaining rows should have 1 as the parent
                $this->assertEquals(1, $result->parent_id);
            }
        }
    }

    /**
     * @covers ::execute
     */
    public function test_reset_parent()
    {
        $action = new RegularAction;
        AuditTrail::create()->action($action)->userId(1)->execute();
        AuditTrail::create()->action($action)->userId(1)->execute();
        AuditTrail::create()->action($action)->userId(1)->execute();
        AuditTrail::create()->action($action)->userId(1)->resetParent()->execute();
        AuditTrail::create()->action($action)->userId(1)->execute();
        AuditTrail::create()->action($action)->userId(1)->execute();

        $results = DB::table('audit_trail')->get();
        $this->assertCount(6, $results);

        foreach ($results as $result) {
            if (in_array($result->id, [1, 4])) {
                // First and fourth rows should have no parent
                $this->assertNull($result->parent_id);
            }
            if (in_array($result->id, [2, 3])) {
                $this->assertEquals(1, $result->parent_id);
            }
            if (in_array($result->id, [5, 6])) {
                $this->assertEquals(4, $result->parent_id);
            }
        }
    }

    /**
     * @covers ::createNewAction
     */
    public function test_createNewAction()
    {
        $action = new EloquentAction(new Foo);
        $auditTrail = resolve(AuditTrail::class);
        $command = AuditTrail::create()->action($action);

        $this->assertCount(0, DB::table('audit_trail_actions')->get());
        $auditTrail->createNewAction($command);

        $results = DB::table('audit_trail_actions')->get();
        $this->assertCount(1, $results);
        $result = $results->first();
        $this->assertEquals($action->getName(), $result->id);
    }

    /**
     * @covers ::cacheAvailableActions
     */
    public function test_cacheAvailableActions()
    {
        $availableActions = $this->setupActions();
        $action = new RegularAction;
        $auditTrail = resolve(AuditTrail::class);
        $command = AuditTrail::create()->action($action);

        $actions = $auditTrail->cacheAvailableActions($command);

        $this->assertEquals($actions, $availableActions);

        // Manually delete the actions table but results should be cached
        DB::table('audit_trail_actions')->delete();
        $this->assertCount(0, DB::table('audit_trail_actions')->get());

        $actions = $auditTrail->cacheAvailableActions($command);
        $this->assertEquals($actions, $availableActions);
    }

    /**
     * @covers ::cacheAvailableActions
     */
    public function test_force_delete_cacheAvailableActions()
    {
        $availableActions = $this->setupActions();

        $action = new RegularAction;
        $auditTrail = resolve(AuditTrail::class);
        $command = AuditTrail::create()->action($action);

        $cachedActions = $auditTrail->cacheAvailableActions($command);
        $this->assertEquals($availableActions, $cachedActions);

        // Manually delete the actions table
        DB::table('audit_trail_actions')->delete();
        $this->assertCount(0, DB::table('audit_trail_actions')->get());

        $cachedActions = $auditTrail->cacheAvailableActions($command);
        $this->assertEquals($availableActions, $cachedActions);

        // Force recreate cache but we deleted the actions table so no actions should be available.
        $cachedActions = $auditTrail->cacheAvailableActions($command, true);
        $this->assertEquals([], $cachedActions);
    }

    /**
     * @covers ::actionExists
     */
    public function test_actionExists()
    {
        $this->setupActions();

        $auditTrail = resolve(AuditTrail::class);
        $commandWithKnownAction = AuditTrail::create()->action(new RegularAction);
        $commandWithUnknownAction = AuditTrail::create()->action(new EloquentAction(new Foo));

        $this->assertTrue($auditTrail->actionExists($commandWithKnownAction));
        $this->assertFalse($auditTrail->actionExists($commandWithUnknownAction));
    }

    /**
     * Returns true if the passed date has happened in the last few seconds. We can't really know when a record has been
     * created in the database because the seconds counter might have changed between we create the record and the time
     * that we read it. So if the record was created in the last few seconds then it's good enought for us.
     * @param string $date MySQL formatted DATETIME
     * @return bool
     */
    public function dateIsCurrent(string $date): bool
    {
        $incomingDate = new Carbon($date);
        return $incomingDate->diffInSeconds(Carbon::now()) < 3;
    }

    /**
     * Create new fields in the audit_trail table
     * @param array $fields
     */
    public function createExtraFields(array $fields)
    {
        Schema::table('audit_trail', function (Blueprint $table) use ($fields) {
            foreach ($fields as $field) {
                $table->string($field)->nullable();
            }
        });
    }

    /**
     * Create a simple users table
     */
    public function setupUsers()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
        });

        User::create(['name' => 'Test1']);
        User::create(['name' => 'Test2']);
        User::create(['name' => 'Test3']);
        User::create(['name' => 'Test4']);
    }

    /**
     * Create a simple actions table
     * @return array with available actions
     */
    public function setupActions(): array
    {
        $actions = [
            'action1',
            'action2',
            'action3',
            'regular-action',
        ];

        foreach ($actions as $action) {
            DB::table('audit_trail_actions')->insert(['id' => $action]);
        }

        return $actions;
    }

    /**
     * Login a user identified by the passed id.
     * @param $id
     */
    public function loginUser($id)
    {
        Auth::login(User::findOrFail($id));
    }
}
