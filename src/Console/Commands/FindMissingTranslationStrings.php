<?php

namespace CodingSocks\LostInTranslation\Console\Commands;

use CodingSocks\LostInTranslation\LostInTranslation;
use CodingSocks\LostInTranslation\NonStringArgumentException;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Output\OutputInterface;

class FindMissingTranslationStrings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lost-in-translation:find
                            {locale : The locale to be checked}
                            {--sorted : Sorts the values before printing}';

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

        $this->withProgressBar($files, function ($file) use ($lit, $locale, &$reported, &$keys) {
            $nodes = $lit->findInFile($file);

            $translationKeys = $this->resolveFirstArgs($lit, $nodes);

            foreach ($translationKeys as $key) {
                if (!Lang::has($key, $locale) && !array_key_exists($key, $reported)) {
                    // TODO: find a better way to check uniqueness
                    $reported[$key] = true;
                    $keys[] = $key;
                }
            }
        });

        $this->newLine();

        if ($this->option('sorted')) {
            sort($keys);
        }

        foreach ($keys as $key) {
            $this->line(OutputFormatter::escape($key));
        }
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