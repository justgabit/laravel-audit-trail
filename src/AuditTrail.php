<?php


namespace Mueva\AuditTrail;

use Carbon\Carbon;
use DB;
use Auth;
use Illuminate\Database\Eloquent\Model;
use Mueva\AuditTrail\Exceptions\AutodetectUserIdException;
use Cache;

class AuditTrail
{
    /**
     * Store the latest inserted row id so that we can set the following rows' parent_id to this.
     * @var int
     */
    protected $lastParentId;

    /**
     * Command factory.
     * @return Command
     */
    public static function create()
    {
        return new Command;
    }

    /**
     * Execute a command.
     * @param Command $command
     */
    public function execute(Command $command)
    {
        /** @var Action $action */
        $action = $command->action;

        if (!$this->actionExists($command)) {
            $this->createNewAction($command);
        }

        if ($command->userIdIsUnknown()) {
            $command->userId = $this->autodetectUserid();
        }

        if ($action->getEloquentModel() instanceof Model) {
            // Action contains en Eloquent model so introspect it to get the table name and table id
            /** @var Model $model */
            $model = $action->getEloquentModel();
            $tableName = $model->getTable();

            $tableId = null;
            if (!is_array($model->getKeyName())) {
                $key = $model->getKeyName();
                $tableId = $model->{$key};
            } else {
                foreach ($model->getKeyName() as $key) {
                    $tableId[$key] = $model->{$key};
                };
            }
        } else {
            // Action does not contain an Eloquen model. Use configured table name and id.
            $tableName = $action->getTableName();
            $tableId = $action->getTableId();
        }

        if ($command->resetParent === true) {
            // Force reset parent_id
            $this->lastParentId = null;
        }

        if (config('audit-trail.encrypt_action_data')) {
            $actionData = encrypt($action->jsonSerialize());
        } else {
            $actionData = $action->jsonSerialize();
        }
        $insertId = DB::connection($command->getConnection())
            ->table($command->getTable())
            ->insertGetId(
                array_merge(
                    [
                        'parent_id'    => $this->lastParentId,
                        'action_id'    => $action->getName(),
                        'user_id'      => $command->userId,
                        'action_data'  => $actionData,
                        'action_brief' => $action->getBrief(),
                        'table_name'   => $tableName,
                        'table_id'     => is_null($tableId) ? null : json_encode($tableId),
                        'date'         => Carbon::now(),
                    ],
                    $command->getExtras(),
                    $action->getExtras()
                )
            );

        if (is_null($this->lastParentId)) {
            $this->lastParentId = $insertId;
        }
    }

    /**
     * Create a new action in the actions table.
     * @param Command $command
     */
    public function createNewAction(Command $command)
    {
        DB::connection($command->getConnection())
            ->table($command->getTable() . '_actions')
            ->insertGetId([
                'id'         => $command->action->getName(),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

        $this->cacheAvailableActions($command, true);
    }

    /**
     * Cache all available actions. If a cache already exists then it doesn't overwrite it unless force is true.
     * @param Command $command
     * @param bool    $force Force cache update even if cache exists
     * @return array
     */
    public function cacheAvailableActions(Command $command, $force = false)
    {
        $connection = $command->getConnection();

        if ($force) {
            Cache::store(config('audit-trail.cache_store'))
                ->forget($command->getCacheKey());
        }

        return Cache::store(config('audit-trail.cache_store'))
            ->rememberForever($command->getCacheKey(), function () use ($command, $connection) {
                return DB::connection($connection)
                    ->table($command->getTable() . '_actions')
                    ->select('id')
                    ->get()
                    ->pluck('id')
                    ->toArray();
            });
    }

    /**
     * Checks if an action exists in the cache.
     * @param Command $command
     * @return bool
     */
    public function actionExists(Command $command): bool
    {
        $this->cacheAvailableActions($command);
        $actions = Cache::store(config('audit-trail.cache_store'))
            ->get($command->getCacheKey());

        return in_array($command->action->getName(), $actions);
    }

    /**
     * Get the user from Laravel's Auth system. This is always an Eloquent model so we can be sure that we can extract
     * its id.
     * @return mixed
     */
    public function autodetectUserid()
    {
        $user = Auth::getUser();

        if (!($user instanceof Model)) {
            throw new AutodetectUserIdException('Can only autodetect userId if authenticated is an Eloquent Model');
        }

        return $user->{$user->getKeyName()};
    }
}
