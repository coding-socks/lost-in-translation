<?php

namespace CodingSocks\LostInTranslation\Tests;

use CodingSocks\LostInTranslation\LostInTranslation;

class LostInTranslationServiceProviderTest extends TestCase
{
    public function testMake()
    {
        $instance = $this->app->make('lost-in-translation');

        $this->assertInstanceOf(LostInTranslation::class, $instance);
    }

    public function testDefaultConfig()
    {
        $config = $this->app->make('config')->get('lost-in-translation');

        $this->assertArrayHasKey('paths', $config);
        $this->assertIsArray($config['paths']);
        $this->assertCount(2, $config['paths']);
        $this->assertIsString($config['paths'][0]);
        $this->assertIsString($config['paths'][1]);

        $this->assertArrayHasKey('locale', $config);
        $this->assertEquals('en', $config['locale']);

        $this->assertArrayHasKey('detect', $config);
        $this->assertEquals([
            'function' => ['__', 'trans', 'trans_choice'],
            'method-function' => [
                ["app('translator')", 'get'],
            ],
            'method-static' => [
                ["\App::make('translator')", 'get'],
                ["\Illuminate\Support\Facades\App::make('translator')", 'get'],
            ],
            'static' => [
                ['\Lang', 'get'],
                ['\Illuminate\Support\Facades\Lang', 'get'],
            ],
        ], $config['detect']);
    }
}