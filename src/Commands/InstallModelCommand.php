<?php

namespace DirectoryTree\PrivacyFilter\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
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

        if (File::exists($modelPath) && ! $this->option('force')) {
            $this->warn('The privacy-filter model already exists. Use --force to overwrite it.');

            return self::SUCCESS;
        }

        $this->download($url, $modelPath);

        $this->info('Privacy-filter model installed.');

        return self::SUCCESS;
    }

    /**
     * Download the model to the configured path.
     */
    protected function download(string $url, string $target): void
    {
        $directory = dirname($target);

        File::ensureDirectoryExists($directory);

        $temporaryTarget = $target.'.tmp';

        FileDownloader::make($this->output)->download($url, $temporaryTarget, 'model');

        if (! rename($temporaryTarget, $target)) {
            File::delete($temporaryTarget);

            throw new RuntimeException("Unable to move model into [{$target}].");
        }
    }
}
