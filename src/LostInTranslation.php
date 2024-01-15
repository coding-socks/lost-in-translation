<?php

namespace CodingSocks\LostInTranslation;

use Illuminate\View\Compilers\BladeCompiler;
use Symfony\Component\Finder\SplFileInfo;

class LostInTranslation
{
    /** @var \Illuminate\View\Compilers\BladeCompiler */
    private BladeCompiler $compiler;

    /** @var array  */
    private array $detect;

    public function __construct(
        BladeCompiler $compiler,
        array $detect,
    ) {
        $this->compiler = $compiler;
        $this->detect = $detect;
    }

    /**
     * Find occurrences of translations in a file.
     *
     * @param \Symfony\Component\Finder\SplFileInfo $file
     * @return \PhpParser\Node[]
     */
    public function findInFile(SplFileInfo $file): array
    {
        $finder = new TranslationFinder($this->compiler, $this->detect);
        return $finder->findInFile($file);
    }

    /**
     * Resolve the first argument of a node.
     *
     * @param $node
     * @return string|null
     * @throws \CodingSocks\LostInTranslation\NonStringArgumentException
     */
    public function resolveFirstArg($node): ?string
    {
        $resolver = new FirstArgumentResolver();
        return $resolver->resolve($node);
    }
}