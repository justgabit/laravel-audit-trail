<?php

namespace Mueva\AuditTrail\Tests\Actions;

use Mueva\AuditTrail\Action;
use Mueva\AuditTrail\Tests\Models\Compound;

class CompoundAction extends Action
{
    /**
     * @var Compound
     */
    protected $compound;

    public function __construct(Compound $compound)
    {
        $this->compound = $compound;
    }

    public function jsonSerialize(): string
    {
        return json_encode($this->compound->toJson());
    }

    public function getEloquentModel()
    {
        return $this->compound;
    }

    public function getBrief()
    {
        return sprintf(
            'Compound action with keys: %s, %s, %s',
            $this->compound->key1,
            $this->compound->key2,
            $this->compound->key3
        );
    }
}
