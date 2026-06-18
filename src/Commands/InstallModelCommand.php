<?php

namespace DirectoryTree\PrivacyFilter\Commands;

use Illuminate\Console\Command;
use RuntimeException;

/**
 * Install the GGUF model used by privacy-filter.
 */
class InstallModelCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'privacy-filter:install-model
        {--url= : GGUF model URL to install}
        {--force : Overwrite the existing model file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install the GGUF model used by privacy-filter.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $url = $this->option('url') ?: config('privacy-filter.model.url');
        $modelPath = config('privacy-filter.paths.model');

        $this->info('Installing privacy-filter model.');
        $this->line("Target: {$modelPath}");

        if (file_exists($modelPath) && ! $this->option('force')) {
            $this->warn('The privacy-filter model already exists. Use --force to overwrite it.');

            return self::SUCCESS;
        }

        try {
            $this->download($url, $modelPath);
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Privacy-filter model installed.');

        return self::SUCCESS;
    }

    /**
     * Download the model to the configured path.
     */
    protected function download(string $url, string $target): void
    {
        $directory = dirname($target);

        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new RuntimeException("Unable to create model directory [{$directory}].");
        }

        $temporaryTarget = $target.'.tmp';

        (new FileDownloader($this->output))->download($url, $temporaryTarget, 'model');

        if (! rename($temporaryTarget, $target)) {
            @unlink($temporaryTarget);

            throw new RuntimeException("Unable to move model into [{$target}].");
        }
    }
}
