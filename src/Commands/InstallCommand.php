<?php

namespace DirectoryTree\PrivacyFilter\Commands;

use Illuminate\Console\Command;

/**
 * Install all privacy-filter assets required by an application.
 */
class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'privacy-filter:install
        {--binary-url= : Binary archive URL to install}
        {--model-url= : GGUF model URL to install}
        {--force : Overwrite existing binary and model files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install the privacy-filter binary and GGUF model.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $force = (bool) $this->option('force');

        $binaryExitCode = $this->call('privacy-filter:install-binary', [
            '--url' => $this->option('binary-url'),
            '--force' => $force,
        ]);

        if ($binaryExitCode !== self::SUCCESS) {
            return $binaryExitCode;
        }

        return $this->call('privacy-filter:install-model', [
            '--url' => $this->option('model-url'),
            '--force' => $force,
        ]);
    }
}
