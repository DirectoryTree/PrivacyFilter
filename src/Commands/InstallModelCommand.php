<?php

namespace DirectoryTree\PrivacyFilter\Commands;

use Illuminate\Console\Command;

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

        $this->components->info('Preparing privacy-filter model installation.');

        $this->components->twoColumnDetail('URL', $url);
        $this->components->twoColumnDetail('Target', $modelPath);

        $this->newLine();
        $this->components->warn('Model download will be implemented next.');

        return self::SUCCESS;
    }
}
