<?php

namespace DirectoryTree\PrivacyFilter\Commands;

use Illuminate\Console\Command;

/**
 * Install the compiled privacy-filter binary for the host platform.
 */
class InstallBinaryCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'privacy-filter:install-binary
        {--release= : GitHub release tag to install}
        {--force : Overwrite the existing binary files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install the privacy-filter binary for the current operating system.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $repository = config('privacy-filter.release.repository');
        $release = $this->option('release') ?: config('privacy-filter.release.version');
        $binaryPath = config('privacy-filter.paths.binary');

        $this->components->info('Preparing privacy-filter binary installation.');

        $this->components->twoColumnDetail('Repository', $repository);
        $this->components->twoColumnDetail('Release', $release);
        $this->components->twoColumnDetail('Target', $binaryPath);

        $this->newLine();
        $this->components->warn('Binary download and extraction will be implemented next.');

        return self::SUCCESS;
    }
}
