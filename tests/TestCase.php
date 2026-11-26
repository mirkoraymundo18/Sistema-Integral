<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        $app = parent::createApplication();
        $connection = $app['config']->get('database.default');
        $database = $app['config']->get("database.connections.{$connection}.database");

        if (!$app->environment('testing') || $connection !== 'sqlite' || $database !== ':memory:') {
            throw new \RuntimeException('Pruebas detenidas: se exige entorno testing y SQLite en memoria. No se modificó la base operativa.');
        }

        return $app;
    }
}
