<?php

namespace CodingSocks\LostInTranslation\Console\Commands;

use CodingSocks\LostInTranslation\LostInTranslation;
use CodingSocks\LostInTranslation\MissingTranslationFileVisitor;
use Countable;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
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
                            {--sorted : Sort the values before printing}
                            {--no-progress : Do not show the progress bar}';

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
            ->unique()
            ->filter(function (\SplFileInfo $file) {
                return Str::endsWith($file->getExtension(), 'php');
            });

        $visitor = new MissingTranslationFileVisitor($locale, $lit);

        $this->trackProgress($files, $visitor);

        $this->printErrors($visitor->getErrors(), $this->output->getErrorStyle());

        $missing = array_unique($visitor->getTranslations());

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
     * @param callable $callback
     * @return void
     */
    protected function trackProgress(Countable|array $totalSteps, callable $callback)
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
}