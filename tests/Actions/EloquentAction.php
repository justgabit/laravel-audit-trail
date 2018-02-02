<?php

namespace Mueva\AuditTrail\Tests\Actions;

use Mueva\AuditTrail\Action;
use Mueva\AuditTrail\Tests\Models\Foo;

class EloquentAction extends Action
{
    protected $model;

    public function __construct(Foo $foo)
    {
        $this->model = $foo;
    }

    public function jsonSerialize(): string
    {
        return $this->model->toJson();
    }

    public function getBrief()
    {
        return sprintf('Eloquent action with id %d', $this->model->id);
    }

    public function getEloquentModel()
    {
        return $this->model;
    }
}
