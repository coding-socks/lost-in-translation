<?php

namespace CodingSocks\LostInTranslation\Tests;

use CodingSocks\LostInTranslation\LostInTranslationServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getPackageProviders($app)
    {
        return [
            LostInTranslationServiceProvider::class,
        ];
    }
}