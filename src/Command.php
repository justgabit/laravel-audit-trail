<?php


namespace Mueva\AuditTrail;

use Illuminate\Support\Fluent;

class Command extends Fluent
{
    const UNKNOWN = 'unknown';

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

    public function __construct()
    {
        parent::__construct();

        // NOTE: NULL is a valid value for userId but the Fluent class considers a value that is not set and null as
        // the same. We need a special mechanism to detect when userId is actually null.
        $this->userId = self::UNKNOWN;
    }

    /**
     * Returns true if userId is a number or NULL (which are both valid values for userId). Otherwise returns false.
     * @return bool
     */
    public function userIdIsUnknown(): bool
    {
        if ($this->userId == self::UNKNOWN) {
            return true;
        }
        return false;
    }

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

    public function userId($id)
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
