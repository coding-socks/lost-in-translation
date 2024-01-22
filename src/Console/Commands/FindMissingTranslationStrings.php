<?php

namespace CodingSocks\LostInTranslation\Console\Commands;

use CodingSocks\LostInTranslation\LostInTranslation;
use CodingSocks\LostInTranslation\MissingTranslationFileVisitor;
use Countable;
use Illuminate\Console\Command;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
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

    /** @var \Illuminate\Contracts\Translation\Translator The translator instance. */
    protected Translator $translator;

    /** @var \Illuminate\Filesystem\Filesystem The filesystem instance. */
    protected Filesystem $files;

    /**
     * @param \Illuminate\Contracts\Translation\Translator $translator
     * @param \Illuminate\Filesystem\Filesystem $files
     */
    public function __construct(Translator $translator, Filesystem $files)
    {
        parent::__construct();

        $this->translator = $translator;
        $this->files = $files;
    }

    /**
     * Execute the console command.
     */
    public function handle(LostInTranslation $lit)
    {
        $baseLocale = config('lost-in-translation.locale');
        $locale = $this->argument('locale');

        if ($baseLocale === $locale) {
            $this->error("Locale `{$locale}` must be different from `{$baseLocale}`.");

            return;
        }

        $missing = $this->findInArray($baseLocale, $locale);

        $files = $this->collectFiles();

        $visitor = new MissingTranslationFileVisitor($locale, $lit, $this->translator);

        $this->traverse($files, $visitor);

        $this->printErrors($visitor->getErrors(), $this->output->getErrorStyle());

        $missing = $missing->merge($visitor->getTranslations())->unique();

        if ($this->option('sorted')) {
            $missing = $missing->sort();
        }

        foreach ($missing as $key) {
            $this->line(OutputFormatter::escape($key));
        }
    }

    /**
     * Execute a given callback while advancing a progress bar.
     *
     * @param \Countable|array $totalSteps
     * @param callable $callback
     * @return void
     */
    protected function traverse(Countable|array $totalSteps, callable $callback)
    {
        if ($this->option('no-progress')) {
            foreach ($totalSteps as $value) {
                $callback($value);
            }

            return;
        }

        $bar = $this->output->createProgressBar(count($totalSteps));

        $bar->start();

        foreach ($totalSteps as $value) {
            $callback($value);

            $bar->advance();
        }

        $bar->finish();
        $bar->clear();
    }

    /**
     * @param array $errors
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return void
     */
    protected function printErrors(array $errors, OutputInterface $output): void
    {
        if ($this->output->getVerbosity() >= $this->parseVerbosity(OutputInterface::VERBOSITY_VERBOSE)) {
            foreach ($errors as $error) {
                $output->writeln($error);
            }
        }
    }

    /**
     * @param string $baseLocale
     * @param string|null $locale
     * @return \Illuminate\Support\Collection
     */
    protected function findInArray(mixed $baseLocale, string|null $locale)
    {
        return Collection::make($this->files->files(lang_path($baseLocale)))
            ->mapWithKeys(function (SplFileInfo $file) {
                return [$file->getFilenameWithoutExtension() => $this->translator->get($file->getFilenameWithoutExtension())];
            })
            ->dot()
            ->keys()
            ->filter(function ($key) use ($locale) {
                return !$this->translator->hasForLocale($key, $locale);
            });
    }

    /**
     * @return mixed
     */
    protected function collectFiles()
    {
        return Collection::make(config('lost-in-translation.paths'))
            ->map(function (string $path) {
                return $this->files->allFiles($path);
            })
            ->flatten()
            ->unique()
            ->filter(function (\SplFileInfo $file) {
                return Str::endsWith($file->getExtension(), 'php');
            });
    }
}