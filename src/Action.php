<?php

namespace Mueva\AuditTrail;

use JsonSerializable;

abstract class Action implements JsonSerializable
{
    /**
     * Return the table that this action relates to. This is a shortcut to save some typing while creating a trail.
     * If null is returned then the user must specify a tableName() while the trail is created.
     * If the action represents an Eloquent model, this method can be ommited. @see getEloquentModel()
     * @return string|null
     */
    public function getTableName()
    {
        return null;
    }

    /**
     * Return the table's primary key that this action relates to. This is a shortcut to save some typing while creating
     * a trail.
     * If null is returned then the user must specify a tableId() key while the trail is created.
     * If the action represents an Eloquent model, this method can be ommited. @see getEloquentModel()
     * @return integer|null
     */
    public function getTableId()
    {
        return null;
    }

    /**
     * Return a brief description of what happened during the action.
     * Eg: "User ## logged in", or "System deleted ## old notifications", etc.
     * @return string|null
     */
    public function getBrief()
    {
        return null;
    }

    /**
     * Returns the string based action name. By default it uses a kebab case of the class name but can be customized by
     * overriding this method.
     * @return string
     */
    public function getName(): string
    {
        return kebab_case(class_basename(static::class));
    }

    /**
     * If this action simply represents an Eloquent model, it can override this method and return the model right here.
     * This eliminates the need to override getTableName() and getTableId() because they can be deduced from the
     * Eloquent model returned here.
     * @return null
     */
    public function getEloquentModel()
    {
        return null;
    }
}