<?php

namespace CodingSocks\LostInTranslation\Console\Commands;

use Closure;
use CodingSocks\LostInTranslation\LostInTranslation;
use CodingSocks\LostInTranslation\NonStringArgumentException;
use Countable;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\SplFileInfo;

class FindMissingTranslationStrings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lost-in-translation:find
                            {locale : The locale to be checked}
                            {--sorted : Sort the values before printing}
                            {--no-progress : Do not show the progress bar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Find missing translation strings in your Laravel blade files';

    /**
     * Buffer for invalid arguments.
     *
     * @var string[]
     */
    protected $invalidArguments;

    /**
     * Execute the console command.
     */
    public function handle(LostInTranslation $lit)
    {
        $locale = $this->argument('locale');
        $baseLocale = config('lost-in-translation.locale');

        if ($locale === $baseLocale) {
            $this->error("Locale `{$locale}` must be different from `{$baseLocale}`.");

            return;
        }

        $files = Collection::make(config('lost-in-translation.paths'))
            ->map(function (string $path) {
                return File::allFiles($path);
            })
            ->flatten()
            ->unique()->filter(function (\SplFileInfo $file) {
                return Str::endsWith($file->getExtension(), 'php');
            });

        $missing = $this->trackProgress($files, $this->makeMissingTranslationFinderCallback($lit, $locale));

        $missing = array_unique(array_merge(...$missing));

        if ($this->output->getVerbosity() >= $this->parseVerbosity(OutputInterface::VERBOSITY_VERBOSE)) {
            $errOutput = $this->output->getErrorStyle();

            foreach ($this->invalidArguments as $argument) {
                $errOutput->writeln("skipping dynamic language key: `{$argument}`");
            }
        }

        if ($this->option('sorted')) {
            sort($missing);
        }

        foreach ($missing as $key) {
            $this->line(OutputFormatter::escape($key));
        }
    }

    /**
     * Execute a given callback while advancing a progress bar.
     *
     * @param \Countable|array $totalSteps
     * @param \Closure $callback
     * @return array
     */
    protected function trackProgress(Countable|array $totalSteps, Closure $callback)
    {
        $items = [];

        if ($this->option('no-progress')) {
            foreach ($totalSteps as $value) {
                if (($item = $callback($value)) && $item !== null) {
                    $items[] = $item;
                }
            }

            return $items;
        }

        $bar = $this->output->createProgressBar(count($totalSteps));

        $bar->start();

        foreach ($totalSteps as $value) {
            if (($item = $callback($value)) && $item !== null) {
                $items[] = $item;
            }

            $bar->advance();
        }

        $bar->finish();
        $bar->clear();

        return $items;
    }

    /**
     * @param \CodingSocks\LostInTranslation\LostInTranslation $lit
     * @param array $nodes
     * @return array
     */
    protected function resolveFirstArgs(LostInTranslation $lit, array $nodes): array
    {
        $translationKeys = [];
        foreach ($nodes as $node) {
            try {
                if (($key = $lit->resolveFirstArg($node)) !== null) {
                    $translationKeys[] = $key;
                }
            } catch (NonStringArgumentException $e) {
                $this->invalidArguments[] = $e->argument;
            }
        }
        return array_unique($translationKeys);
    }

    /**
     * @param \CodingSocks\LostInTranslation\LostInTranslation $lit
     * @param string|null $locale
     * @return \Closure
     */
    protected function makeMissingTranslationFinderCallback(LostInTranslation $lit, string|null $locale): Closure
    {
        return function (SplFileInfo $file) use ($lit, $locale) {
            $nodes = $lit->findInFile($file);

            $translationKeys = $this->resolveFirstArgs($lit, $nodes);

            $missing = [];

            foreach ($translationKeys as $key) {
                if (!Lang::has($key, $locale)) {
                    // TODO: find a better way to check uniqueness
                    $missing[] = $key;
                }
            }

            return $missing;
        };
    }
}