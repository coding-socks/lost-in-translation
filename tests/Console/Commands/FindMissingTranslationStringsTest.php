<?php

namespace CodingSocks\LostInTranslation\Tests\Console\Commands;

use CodingSocks\LostInTranslation\LostInTranslationServiceProvider;
use CodingSocks\LostInTranslation\Tests\TestCase;
use Illuminate\Config\Repository;
use Illuminate\Support\Facades\Artisan;

class FindMissingTranslationStringsTest extends TestCase
{
    public function testBasicCommand(): void
    {
        $exit_code = $this
            ->withoutMockingConsoleOutput()
            ->artisan('lost-in-translation:find ja --no-progress --sorted');

        $output = Artisan::output();

        $this->assertSame("foobar\nglobal_key\nmessages.namespaced_key\n", $output);
        $this->assertSame(0, $exit_code);
    }

    public function testMissingLocale(): void
    {
        $exit_code = $this
            ->withoutMockingConsoleOutput()
            ->artisan('lost-in-translation:find zh --no-progress --sorted');

        $output = Artisan::output();

        $this->assertSame("foobar\nglobal_key\nkey_in_both\nmessages.namespaced_key\n", $output);
        $this->assertSame(0, $exit_code);
    }

    public function defineEnvironment($app)
    {
        $appPath = __DIR__ . '/../../application';
        $app->useLangPath($appPath . '/lang');

        tap($app['config'], static function (Repository $config) use ($appPath) {
            $config->set('lost-in-translation.paths', [
                $appPath . '/resources/views'
            ]);
        });
    }
}
