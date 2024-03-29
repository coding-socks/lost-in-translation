<?php

namespace CodingSocks\LostInTranslation\Tests\NodeFinder;

use CodingSocks\LostInTranslation\NodeFinder\TranslationFinder;
use CodingSocks\LostInTranslation\Tests\TestCase;

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
{{ __('keep') }}
EOD;

        $finder = new TranslationFinder(
            config('lost-in-translation.detect')
        );

        $compiler = $this->app->make('blade.compiler');
        $str = $compiler->compileString($str);

        $nodes = $finder->find($str);

        $this->assertCount(3, $nodes);
    }

    public function testFunctionCall()
    {
        $str = <<<'EOD'
<?php
something('skip');

__('keep');
trans('keep');
trans_choice('keep', 10);
EOD;

        $finder = new TranslationFinder(
            config('lost-in-translation.detect')
        );

        $nodes = $finder->find($str);

        $this->assertCount(3, $nodes);
    }

    public function testStaticCall()
    {
        $str = <<<'EOD'
<?php
Lang::something('skip');

Lang::get('keep');
\Lang::get('keep');
Illuminate\Support\Facades\Lang::get('keep');
\Illuminate\Support\Facades\Lang::get('keep');
EOD;

        $finder = new TranslationFinder(
            config('lost-in-translation.detect')
        );

        $nodes = $finder->find($str);

        $this->assertCount(4, $nodes);
    }

    public function testMethodCall()
    {
        $str = <<<'EOD'
<?php
app('something')->get('skip');
app('translator')->something('skip');
App::make('something')->get('skip');
App::make('translator')->something('skip');

app('translator')->get('keep');
App::make('translator')->get('keep');
\App::make('translator')->get('keep');
Illuminate\Support\Facades\App::make('translator')->get('keep');
\Illuminate\Support\Facades\App::make('translator')->get('keep');
EOD;

        $finder = new TranslationFinder(
            config('lost-in-translation.detect')
        );

        $nodes = $finder->find($str);

        $this->assertCount(5, $nodes);
    }
}