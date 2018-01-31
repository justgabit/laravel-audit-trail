<?php


namespace Mueva\AuditTrail;

use Illuminate\Support\Fluent;

class Command extends Fluent
{
    /**
     * There are all the keys that this command recognizes. This is needed so that the command can recognize extra keys.
     * @var array
     */
    public $allowedKeys = [
        'action',
        'connection',
        'execute',
        'userId',
        'resetParent',
        'table',
    ];

    public function action(Action $action)
    {
        $this->action = $action;
        return $this;
    }

    public function connection(string $connection)
    {
        $this->connection = $connection;
        return $this;
    }

    public function execute()
    {
        $auditTrail = resolve(AuditTrail::class);
        $auditTrail->execute($this);
    }

    public function userId(int $id)
    {
        $this->userId = $id;
        return $this;
    }

    public function resetParent()
    {
        $this->resetParent = true;
        return $this;
    }

    public function table(string $table)
    {
        $this->table = $table;
        return $this;
    }

    /**
     * Get the table name if specified. Otherwise return configured default.
     * @return string
     */
    public function getTable(): string
    {
        if (!isset($this->table)) {
            return config('audit-trail.table_name');
        }

        return $this->table;
    }

    /**
     * Get the databse connection name if specified. Otherwise return configured default.
     * @return string
     */
    public function getConnection(): string
    {
        if (!isset($this->connection)) {
            return config('audit-trail.connection');
        }

        return $this->connection;
    }

    /**
     * A command can have many items in it. Some are for internal use and the rest are "extras". This method returns all
     * the extra items a command has.
     * @return array
     */
    public function getExtras(): array
    {
        $extras = [];
        foreach ($this->toArray() as $key => $value) {
            if (!in_array($key, $this->allowedKeys)) {
                $extras[$key] = $value;
            }
        }
        return $extras;
    }

    /**
     * Generate a deterministic cache key for this Command
     * @return string
     */
    public function getCacheKey(): string
    {
        return md5($this->getConnection() . $this->getTable());
    }
}
