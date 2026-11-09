<?php

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function createApplication(): Application
    {
        $app = parent::createApplication();
        $connection = $app['config']->get('database.default');
        $database = $app['config']->get('database.connections.'.$connection.'.database');
        if ($connection === 'mysql' && ! str_ends_with((string) $database, '_test')) {
            throw new \RuntimeException('Database-tests mogen alleen een afzonderlijke database met achtervoegsel _test gebruiken.');
        }

        return $app;
    }
}
