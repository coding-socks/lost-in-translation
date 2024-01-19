<?php

namespace CodingSocks\LostInTranslation;

use PhpParser\NodeTraverser;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;

class TranslationFinder
{
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
        array $detect,
        Parser $parser = null,
        $printer = null
    ) {
        $this->detect = $detect;
        $this->parser = $parser ?? (new ParserFactory())->createForHostVersion();
        $this->printer = $printer ?? new Standard();
    }

    /**
     * Find occurrences of translations in a string.
     *
     * @param string $str
     * @return \PhpParser\Node[]
     */
    public function find(string $str): array
    {
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