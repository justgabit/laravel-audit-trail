<?php

namespace Mueva\AuditTrail\Tests;

use Illuminate\Foundation\Application;
use Mueva\AuditTrail\AuditTrailServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

class TestCase extends OrchestraTestCase
{
    public function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.connections.mysql', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }

    /**
     * Load package service provider
     * @param  Application $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [AuditTrailServiceProvider::class];
    }

    public function setUp()
    {
        parent::setUp();
        $this->artisan('migrate');
    }
}