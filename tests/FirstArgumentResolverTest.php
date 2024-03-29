<?php

namespace CodingSocks\LostInTranslation\Tests;

use CodingSocks\LostInTranslation\FirstArgumentResolver;
use CodingSocks\LostInTranslation\NodeFinder\TranslationFinder;
use CodingSocks\LostInTranslation\NonStringArgumentException;

class FirstArgumentResolverTest extends TestCase
{
    public function testArgumentResolution()
    {
        $nodes = $this->find(<<<'EOD'
@lang('directive-arg')
{{ trans('function-call-arg') }}
{{ Lang::get('static-call-arg') }}
{{ app('translator')->get('method-call-arg') }}

{{ trans('concat' . ' single') }}
{{ trans('concat' . ' ' . 'double') }}
EOD);

        $resolver = new FirstArgumentResolver();

        $this->assertCount(6, $nodes);

        $this->assertEquals('directive-arg', $resolver->resolve($nodes[0]));
        $this->assertEquals('function-call-arg', $resolver->resolve($nodes[1]));
        $this->assertEquals('static-call-arg', $resolver->resolve($nodes[2]));
        $this->assertEquals('method-call-arg', $resolver->resolve($nodes[3]));
        $this->assertEquals('concat single', $resolver->resolve($nodes[4]));
        $this->assertEquals('concat double', $resolver->resolve($nodes[5]));
    }

    public function testNonStringArgumentException()
    {
        $nodes = $this->find(<<<'EOD'
{{ trans($skip) }}
{{ trans('concat' . $skip) }}
{{ trans($skip . 'concat') }}
EOD);

        $resolver = new FirstArgumentResolver();

        $this->assertCount(3, $nodes);

        $exceptions = [];

        try {
            $resolver->resolve($nodes[0]);
        } catch (NonStringArgumentException $e) {
            $exceptions[] = $e;
        }
        try {
            $resolver->resolve($nodes[1]);
        } catch (NonStringArgumentException $e) {
            $exceptions[] = $e;
        }
        try {
            $resolver->resolve($nodes[2]);
        } catch (NonStringArgumentException $e) {
            $exceptions[] = $e;
        }

        $this->assertCount(3, $exceptions);
    }

    protected function find($str): array
    {
        $finder = new TranslationFinder(
            config('lost-in-translation.detect')
        );

        $compiler = $this->app->make('blade.compiler');
        $str = $compiler->compileString($str);

        return $finder->find($str);
    }
}