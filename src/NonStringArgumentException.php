<?php

namespace CodingSocks\LostInTranslation;

class NonStringArgumentException extends \Exception
{
    public $argument;

    public function __construct(string $argument, int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct("found non string argument: {$argument}", $code, $previous);

        $this->argument = $argument;
    }
}