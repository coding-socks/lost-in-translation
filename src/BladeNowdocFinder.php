<?php

namespace CodingSocks\LostInTranslation;

use PhpParser\Node\Scalar\String_;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\FindingVisitor;
use PhpParser\Parser;
use PhpParser\ParserFactory;

class BladeNowdocFinder
{
    /** @var \PhpParser\Parser */
    private Parser $parser;

    /**
     * @param \PhpParser\Parser|null $parser
     */
    public function __construct(
        Parser $parser = null,
    ) {
        $this->parser = $parser ?? (new ParserFactory())->createForHostVersion();
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

        $visitor = new FindingVisitor(function ($node) {
            return $node instanceof String_
                && strtolower($node->getAttribute('docLabel', '')) === 'blade';
        });

        $traverser = new NodeTraverser();
        $traverser->addVisitor($visitor); // For compatibility with ^4.10

        $traverser->traverse($nodes);

        return $visitor->getFoundNodes();
    }
}