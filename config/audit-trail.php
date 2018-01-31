<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default model database connection
    |--------------------------------------------------------------------------
    |
    | The name of the database connection for the models. If you don't specify
    | a database connection while creating a new audit trail item, then this
    | connection will be used.
    | You can find this in config/database.php under the "connections" key.
    |
    */
    'connection' => 'mysql',

    /*
    |--------------------------------------------------------------------------
    | Table name
    |--------------------------------------------------------------------------
    |
    | The table name that will be used in the migrations. Dependent tables will
    | use this as a prefix.
    |
    */
    'table_name' => 'audit_trail',

    /*
    |--------------------------------------------------------------------------
    | Cache name
    |--------------------------------------------------------------------------
    |
    | Which Laravel cache store to use. This will be used to cache the actions
    | table and validate actions classes against the ones in the database.
    | Every time a new action is added, this cache will need to be purged.
    | You can find this in config/cache.php under the "stores" key.
    |
    */
    'cache_store' => 'array',

    /*
    |--------------------------------------------------------------------------
    | Encrypt action data
    |--------------------------------------------------------------------------
    |
    | Action data holds all the information relevant to a particular audit trail.
    | This may contain sensitive data. While it is suggested that you filter any
    | sensitive data in the jsonSerialize() method of the action, you may with to
    | keep it. This option provides a means to encrypt it using Laravel's encrypt()
    | function.
    | IMPORTANT: The action brief is never encrypted as it is meant to be an
    |            abbreviated description of the audit trail used for quick reference.
    */
    'encrypt_action_data' => true,
];
