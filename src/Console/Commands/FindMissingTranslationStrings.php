<?php

namespace CodingSocks\LostInTranslation\Console\Commands;

use Closure;
use CodingSocks\LostInTranslation\LostInTranslation;
use CodingSocks\LostInTranslation\NonStringArgumentException;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Helper\ProgressBar;
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
                            {--sorted : Sort the values before printing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Find missing translation strings in your Laravel blade files';

    /**
     * Execute the console command.
     */
    public function handle(LostInTranslation $lit)
    {
        $locale = $this->argument('locale');

        $files = Collection::make(config('lost-in-translation.paths'))
            ->map(function (string $path) {
                return File::allFiles($path);
            })
            ->flatten()
            ->unique()->filter(function (\SplFileInfo $file) {
                return Str::endsWith($file->getExtension(), 'php');
            });

        $reported = [];
        $keys = [];

        $this->showProgress($files, function (SplFileInfo $file, ProgressBar $bar) use ($lit, $locale, &$reported, &$keys, &$barCopy) {
            $nodes = $lit->findInFile($file);

            $translationKeys = $this->resolveFirstArgs($lit, $nodes);

            foreach ($translationKeys as $key) {
                if (!Lang::has($key, $locale) && !array_key_exists($key, $reported)) {
                    // TODO: find a better way to check uniqueness
                    $reported[$key] = true;
                    $keys[] = $key;
                }
            }
        })->clear();

        if ($this->option('sorted')) {
            sort($keys);
        }

        foreach ($keys as $key) {
            $this->line(OutputFormatter::escape($key));
        }
    }

    /**
     * Execute a given callback while advancing a progress bar.
     *
     * @param  iterable|int  $totalSteps
     * @param  \Closure  $callback
     * @return \Symfony\Component\Console\Helper\ProgressBar
     */
    protected function showProgress($totalSteps, Closure $callback)
    {
        $bar = $this->output->createProgressBar(
            is_iterable($totalSteps) ? count($totalSteps) : $totalSteps
        );

        $bar->start();

        if (is_iterable($totalSteps)) {
            foreach ($totalSteps as $value) {
                $callback($value, $bar);

                $bar->advance();
            }
        } else {
            $callback($bar);
        }

        $bar->finish();

        return $bar;
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
                $this->warn("skipping dynamic language key: `{$e->argument}`", OutputInterface::VERBOSITY_VERBOSE);
            }
        }
        return array_unique($translationKeys);
    }
}