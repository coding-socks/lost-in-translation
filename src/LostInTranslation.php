<?php

namespace CodingSocks\LostInTranslation;

use Illuminate\Support\Str;
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
        $finder = new TranslationFinder($this->detect);

        $str = $file->getContents();

        if ($this->isBladeFile($file)) {
            $str = $this->compiler->compileString($str);

            return $finder->find($str);
        }

        $found = [];
        $nowdocFinder = new BladeNowdocFinder();

        foreach ($nowdocFinder->find($str) as $node) {
            $str = $this->compiler->compileString($node->value);

            array_push($found, ...$finder->find($str));
        }

        return array_merge($found, $finder->find($str));
    }

    /**
     * Determine if the given file is likely a blade file.
     *
     * @param \Symfony\Component\Finder\SplFileInfo $file
     * @return bool
     */
    protected function isBladeFile(SplFileInfo $file): bool
    {
        return Str::endsWith($file->getFilename(), '.blade.php');
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