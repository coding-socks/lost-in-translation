<?php

namespace CodingSocks\LostInTranslation\Tests;

use CodingSocks\LostInTranslation\TranslationFinder;

class TranslationFinderTest extends TestCase
{
    public function testBladeDirective()
    {
        $str = <<<'EOD'
@@lang('skip')
@env('skip')
    @lang('but keep this')
@endenv

@lang('keep')
EOD;

        $finder = new TranslationFinder(
            $this->app->make('blade.compiler'),
            config('lost-in-translation.detect')
        );

        $nodes = $finder->find($str);

        $this->assertCount(2, $nodes);
    }

    public function testFunctionCall()
    {
        $str = <<<'EOD'
{{ something('skip') }}

{{ __('keep') }}
{{ trans('keep') }}
EOD;

        $finder = new TranslationFinder(
            $this->app->make('blade.compiler'),
            config('lost-in-translation.detect')
        );

        $nodes = $finder->find($str);

        $this->assertCount(2, $nodes);
    }

    public function testStaticCall()
    {
        $str = <<<'EOD'
{{ Lang::something('skip') }}

{{ Lang::get('keep') }}
{{ \Lang::get('keep') }}
{{ Illuminate\Support\Facades\Lang::get('keep') }}
{{ \Illuminate\Support\Facades\Lang::get('keep') }}
EOD;

        $finder = new TranslationFinder(
            $this->app->make('blade.compiler'),
            config('lost-in-translation.detect')
        );

        $nodes = $finder->find($str);

        $this->assertCount(4, $nodes);
    }

    public function testMethodCall()
    {
        $str = <<<'EOD'
{{ app('something')->get('keep') }}
{{ app('translator')->something('keep') }}
{{ App::make('something')->get('skip') }}
{{ App::make('translator')->something('skip') }}

{{ app('translator')->get('keep') }}
{{ App::make('translator')->get('keep') }}
{{ \App::make('translator')->get('keep') }}
{{ Illuminate\Support\Facades\App::make('translator')->get('keep') }}
{{ \Illuminate\Support\Facades\App::make('translator')->get('keep') }}
EOD;

        $finder = new TranslationFinder(
            $this->app->make('blade.compiler'),
            config('lost-in-translation.detect')
        );

        $nodes = $finder->find($str);

        $this->assertCount(5, $nodes);
    }
}