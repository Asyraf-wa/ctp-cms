<?php

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    // RefreshDatabase and DatabaseTruncation wipe tables: never let them near anything but the test database.
    // A cached config (php artisan optimize) ignores phpunit.xml and points the suite at the dev database.
    public function createApplication(): Application
    {
        $app = parent::createApplication();

        $database = $app['config']->get('database.connections.'.$app['config']->get('database.default').'.database');

        if ($app->configurationIsCached() || $database !== 'ctp_testing') {
            throw new RuntimeException(
                'Refusing to run tests against ['.$database.'] (config cached: '.($app->configurationIsCached() ? 'yes' : 'no').'). '
                .'Run `./dev artisan optimize:clear` and try again.',
            );
        }

        return $app;
    }
}
