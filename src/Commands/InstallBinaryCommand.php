<?php

namespace DirectoryTree\PrivacyFilter\Commands;

use DirectoryTree\PrivacyFilter\Support\Directory;
use DirectoryTree\PrivacyFilter\Support\Tar;
use DirectoryTree\PrivacyFilter\Support\TarGz;
use DirectoryTree\PrivacyFilter\Support\Zip;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;

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
        {--url= : Binary archive URL to install}
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
        $binaryPath = config('privacy-filter.paths.binary');
        $repository = config('privacy-filter.release.repository');
        $release = $this->option('release') ?: config('privacy-filter.release.version');

        $asset = $this->getAssetName();

        $url = $this->option('url') ?: $this->getReleaseUrl($repository, $release, $asset);

        $this->info('Installing privacy-filter binary.');
        $this->line("Target: {$binaryPath}");

        if (File::exists($binaryPath) && ! $this->option('force')) {
            $this->warn('The privacy-filter binary already exists. Use --force to overwrite it.');

            return self::SUCCESS;
        }

        $workingDirectory = null;

        try {
            $workingDirectory = $this->getTemporaryWorkingDirectory();

            $archivePath = $workingDirectory.DIRECTORY_SEPARATOR.$this->getArchiveFileName($url, $asset);

            $this->download($url, $archivePath);
            $this->extract($archivePath, $workingDirectory);
            $this->install($workingDirectory, $binaryPath);
        } finally {
            if ($workingDirectory) {
                File::deleteDirectory($workingDirectory);
            }
        }

        $this->info('Privacy-filter binary installed.');

        return self::SUCCESS;
    }

    /**
     * Download the given URL to the target path.
     */
    protected function download(string $url, string $target): void
    {
        FileDownloader::make($this->output)->download($url, $target, 'binary archive');
    }

    /**
     * Extract the downloaded archive into the working directory.
     */
    protected function extract(string $archivePath, string $workingDirectory): void
    {
        match (true) {
            str_ends_with($archivePath, '.zip') => Zip::extract($archivePath, $workingDirectory),
            str_ends_with($archivePath, '.tar') => Tar::extract($archivePath, $workingDirectory),
            str_ends_with($archivePath, '.tgz') => TarGz::extract($archivePath, $workingDirectory),
            str_ends_with($archivePath, '.tar.gz') => TarGz::extract($archivePath, $workingDirectory),
            default => throw new RuntimeException("Unsupported archive format [{$archivePath}]."),
        };
    }

    /**
     * Move the extracted package into the configured location.
     */
    protected function install(string $workingDirectory, string $binaryPath): void
    {
        if (! $source = $this->findExtractedBinary($workingDirectory)) {
            throw new RuntimeException('Unable to locate the privacy-filter binary in the extracted archive.');
        }

        $packageDirectory = dirname($source, 2);
        $installDirectory = $this->getInstallDirectory($binaryPath);

        Directory::copy($packageDirectory, $installDirectory);

        File::chmod($binaryPath, 0755);
    }

    /**
     * Find the privacy-filter executable in an extracted archive.
     */
    protected function findExtractedBinary(string $workingDirectory): ?string
    {
        $binaryName = PHP_OS_FAMILY === 'Windows'
            ? 'privacy-filter.exe'
            : 'privacy-filter';

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($workingDirectory));

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getFilename() === $binaryName) {
                return $file->getPathname();
            }
        }

        return null;
    }

    /**
     * Get the release asset name for the host platform.
     */
    protected function getAssetName(): string
    {
        return match (PHP_OS_FAMILY) {
            'Darwin' => php_uname('m') === 'arm64'
                ? 'privacy-filter-darwin-arm64.tar.gz'
                : 'privacy-filter-darwin-x64.tar.gz',
            'Linux' => 'privacy-filter-linux-x64.tar.gz',
            'Windows' => 'privacy-filter-windows-x64.zip',
            default => throw new RuntimeException('Privacy-filter binaries are not available for ['.PHP_OS_FAMILY.'].'),
        };
    }

    /**
     * Build the GitHub release download URL for the selected asset.
     */
    protected function getReleaseUrl(string $repository, string $release, string $asset): string
    {
        return "https://github.com/{$repository}/releases/download/{$release}/{$asset}";
    }

    /**
     * Resolve the local archive filename.
     */
    protected function getArchiveFileName(string $url, string $fallback): string
    {
        $path = parse_url($url, PHP_URL_PATH);

        if (! is_string($path) || empty($path)) {
            return $fallback;
        }

        return basename($path) ?: $fallback;
    }

    /**
     * Get the installation directory for the binary, ensuring that it is not nested within a 'bin' subdirectory.
     */
    protected function getInstallDirectory(string $binaryPath): string
    {
        $directory = dirname($binaryPath);

        return basename($directory) === 'bin' ? dirname($directory) : $directory;
    }

    /**
     * Create a temporary working directory.
     */
    protected function getTemporaryWorkingDirectory(): string
    {
        $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.'privacy-filter-'.bin2hex(random_bytes(8));

        File::ensureDirectoryExists($path);

        return $path;
    }
}
