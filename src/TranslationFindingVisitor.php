<?php

namespace CodingSocks\LostInTranslation;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\NodeVisitorAbstract;
use PhpParser\PrettyPrinter\Standard;

class TranslationFindingVisitor extends NodeVisitorAbstract
{
    /** @var array Rules for detection */
    protected $detect;

    /** @var Node[] Found nodes */
    protected array $foundNodes;

    /** @var \PhpParser\PrettyPrinter Converts nodes to PHP code */
    private $printer;

    /**
     * @param array $detect
     * @param \PhpParser\PrettyPrinter $printer
     */
    public function __construct(array $detect, $printer = null) {
        $this->detect = $detect;
        $this->printer = $printer ?? new Standard();
    }

    /**
     * Get found nodes satisfying the filter callback.
     *
     * Nodes are returned in pre-order.
     *
     * @return Node[] Found nodes
     */
    public function getFoundNodes(): array {
        return $this->foundNodes;
    }

    public function beforeTraverse(array $nodes): ?array {
        $this->foundNodes = [];

        return null;
    }

    public function enterNode(Node $node) {
        if ($this->check($node)) {
            $this->foundNodes[] = $node;
        }

        return null;
    }

    protected function isInstanceOfTranslationCall($node): bool
    {
        return $node instanceof StaticCall
            || $node instanceof MethodCall
            || $node instanceof FuncCall
            ;
    }

    protected function isNameStringLike($node): bool
    {
        return $node->name instanceof Identifier
            || $node->name instanceof Name
            ;
    }

    protected function check($node): bool
    {
        if (!($this->isInstanceOfTranslationCall($node) && $this->isNameStringLike($node))) {
            return false;
        }
        $name = $node->name->toString();
        if ($node instanceof StaticCall && $node->class instanceof Name) {
            $class = $node->class->toString();
            if ($class[0] != '\\') {
                $class = '\\' . $class;
            }
            foreach ($this->detect['static'] as $call) {
                if ($call[0] === $class && $call[1] === $name) {
                    return true;
                }
            }
            return false;
        }
        if ($node instanceof MethodCall && $node->var instanceof FuncCall) {
            $code = $this->printer->prettyPrint([$node->var]);
            foreach ($this->detect['method-function'] as $call) {
                if ($call[0] === $code && $call[1] === $name) {
                    return true;
                }
            }
            return false;
        }
        if ($node instanceof MethodCall && $node->var instanceof StaticCall) {
            $code = $this->printer->prettyPrint([$node->var]);
            if ($code[0] != '\\') {
                $code = '\\' . $code;
            }
            foreach ($this->detect['method-static'] as $call) {
                if ($call[0] === $code && $call[1] === $name) {
                    return true;
                }
            }
            return false;
        }
        if ($node instanceof FuncCall) {
            return in_array($name, $this->detect['function']);
        }
        return false;
    }
}