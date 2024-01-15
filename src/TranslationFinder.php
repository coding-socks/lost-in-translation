<?php

namespace CodingSocks\LostInTranslation;

use Illuminate\View\Compilers\BladeCompiler;
use PhpParser\NodeTraverser;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use Symfony\Component\Finder\SplFileInfo;

class TranslationFinder
{
    /** @var \Illuminate\View\Compilers\BladeCompiler */
    private BladeCompiler $compiler;

    /** @var array  */
    private array $detect;

    /** @var \PhpParser\Parser */
    private Parser $parser;

    /** @var \PhpParser\PrettyPrinter */
    private $printer;

    /**
     * @param \Illuminate\View\Compilers\BladeCompiler $compiler
     * @param array $detect
     * @param \PhpParser\Parser|null $parser
     * @param \PhpParser\PrettyPrinter $printer
     */
    public function __construct(
        BladeCompiler $compiler,
        array $detect,
        Parser $parser = null,
        $printer = null
    ) {
        $this->compiler = $compiler;
        $this->detect = $detect;
        $this->parser = $parser ?? (new ParserFactory())->createForHostVersion();
        $this->printer = $printer ?? new Standard();
    }

    /**
     * Find occurrences of translations in a file.
     *
     * @param \Symfony\Component\Finder\SplFileInfo $file
     * @return \PhpParser\Node[]
     */
    public function findInFile(SplFileInfo $file): array
    {
        $str = $file->getContents();

        return $this->find($str);
    }

    /**
     * Find occurrences of translations in a string.
     *
     * @param string $str
     * @return \PhpParser\Node[]
     */
    public function find(string $str): array
    {
        $str = $this->compiler->compileString($str);

        $nodes = $this->parser->parse($str);

        if (empty($nodes)) {
            return [];
        }

        $visitor = new TranslationFindingVisitor($this->detect, $this->printer);

        $traverser = new NodeTraverser();
        $traverser->addVisitor($visitor); // For compatibility with ^4.10

        $traverser->traverse($nodes);

        return $visitor->getFoundNodes();
    }
}