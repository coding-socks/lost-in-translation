<?php

namespace CodingSocks\LostInTranslation;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\Concat;
use PhpParser\Node\Scalar\String_;
use PhpParser\PrettyPrinter\Standard;

class FirstArgumentResolver
{
    /** @var \PhpParser\PrettyPrinter */
    private $printer;

    /**
     * @param \PhpParser\PrettyPrinter|null $printer
     */
    public function __construct($printer = null)
    {
        $this->printer = $printer ?? new Standard();
    }

    /**
     * Resolve the first argument of a node.
     *
     * @param $node
     * @return string|null
     * @throws \CodingSocks\LostInTranslation\NonStringArgumentException
     */
    public function resolve($node): ?string
    {
        if (empty($node->args)) {
            return null;
        }
        $firstArg = $node->args[0]->value;
        if ($firstArg instanceof Concat) {
            $firstArg = $this->tryConcat($firstArg);
        }
        if (!($firstArg instanceof String_)) {
            $code = $this->printer->prettyPrint([$firstArg]);
            throw new NonStringArgumentException($code);
        }
        return $firstArg->value;
    }

    /**
     * Concatenates String_ scalars recursively.
     *
     * @param \PhpParser\Node\Expr\BinaryOp\Concat $concat
     * @return \PhpParser\Node\Expr
     */
    protected function tryConcat(Concat $concat): Expr
    {
        if ($concat->left instanceof Concat) {
            $concat->left = $this->tryConcat($concat->left);
        }
        if ($concat->right instanceof Concat) {
            $concat->right = $this->tryConcat($concat->right);
        }

        if (!($concat->left instanceof String_) || !($concat->right instanceof String_)) {
            return $concat;
        }
        return new String_($concat->left->value . $concat->right->value);
    }
}