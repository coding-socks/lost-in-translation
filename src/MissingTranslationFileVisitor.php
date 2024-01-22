<?php

namespace CodingSocks\LostInTranslation;

use Illuminate\Support\Facades\Lang;
use Symfony\Component\Finder\SplFileInfo;

class MissingTranslationFileVisitor
{
    /** @var string[] Buffer for valid arguments. */
    protected $translations = [];

    /** @var string[] Buffer for invalid arguments. */
    protected $errors = [];

    /** @var string */
    protected $locale;

    /** @var \CodingSocks\LostInTranslation\LostInTranslation */
    protected $lit;

    /**
     * @param $locale
     * @param $lit
     */
    public function __construct($locale, $lit)
    {
        $this->locale = $locale;
        $this->lit = $lit;
    }


    public function __invoke(SplFileInfo $file)
    {
        $nodes = $this->lit->findInFile($file);

        $translationKeys = $this->resolveFirstArgs($nodes);

        foreach ($translationKeys as $key) {
            if (!Lang::hasForLocale($key, $this->locale)) {
                $this->translations[] = $key;
            }
        }
    }

    /**
     * @param array $nodes
     * @return array
     */
    protected function resolveFirstArgs(array $nodes): array
    {
        $translationKeys = [];
        foreach ($nodes as $node) {
            try {
                if (($key = $this->lit->resolveFirstArg($node)) !== null) {
                    $translationKeys[] = $key;
                }
            } catch (NonStringArgumentException $e) {
                $this->errors[] = "skipping dynamic language key: `{$e->argument}`";
            }
        }
        return array_unique($translationKeys);
    }

    public function getTranslations(): array
    {
        return $this->translations;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}