<?php

namespace Mueva\AuditTrail\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use LaravelTreats\Model\Traits\HasCompositePrimaryKey;

class Compound extends Model
{
    use HasCompositePrimaryKey;

    protected $primaryKey = ['key1', 'key2', 'key3'];
    protected $table = 'compound';
    protected $fillable = ['key1', 'key2', 'key3'];
}
