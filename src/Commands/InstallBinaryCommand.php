<?php

namespace DirectoryTree\PrivacyFilter\Commands;

use FilesystemIterator;
use Illuminate\Console\Command;
use PharData;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use Symfony\Component\Process\Process;
use ZipArchive;

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
        try {
            $repository = config('privacy-filter.release.repository');
            $release = $this->option('release') ?: config('privacy-filter.release.version');
            $binaryPath = config('privacy-filter.paths.binary');
            $asset = $this->assetName();
            $url = $this->option('url') ?: $this->releaseUrl($repository, $release, $asset);
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Installing privacy-filter binary.');
        $this->line("Target: {$binaryPath}");

        if (file_exists($binaryPath) && ! $this->option('force')) {
            $this->warn('The privacy-filter binary already exists. Use --force to overwrite it.');

            return self::SUCCESS;
        }

        $workingDirectory = null;

        try {
            $workingDirectory = $this->makeWorkingDirectory();
            $archivePath = $workingDirectory.DIRECTORY_SEPARATOR.$this->archiveFileName($url, $asset);

            $this->download($url, $archivePath);
            $this->extract($archivePath, $workingDirectory);
            $this->installBinary($workingDirectory, $binaryPath);
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        } finally {
            if ($workingDirectory !== null) {
                $this->deleteDirectory($workingDirectory);
            }
        }

        $this->info('Privacy-filter binary installed.');

        return self::SUCCESS;
    }

    /**
     * Get the release asset name for the host platform.
     */
    protected function assetName(): string
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
    protected function releaseUrl(string $repository, string $release, string $asset): string
    {
        return "https://github.com/{$repository}/releases/download/{$release}/{$asset}";
    }

    /**
     * Resolve the local archive filename.
     */
    protected function archiveFileName(string $url, string $fallback): string
    {
        $path = parse_url($url, PHP_URL_PATH);

        if (! is_string($path) || $path === '') {
            return $fallback;
        }

        return basename($path) ?: $fallback;
    }

    /**
     * Create a temporary working directory.
     */
    protected function makeWorkingDirectory(): string
    {
        $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.'privacy-filter-'.bin2hex(random_bytes(8));

        if (! mkdir($path, 0755, true) && ! is_dir($path)) {
            throw new RuntimeException("Unable to create temporary directory [{$path}].");
        }

        return $path;
    }

    /**
     * Download the given URL to the target path.
     */
    protected function download(string $url, string $target): void
    {
        (new FileDownloader($this->output))->download($url, $target, 'binary archive');
    }

    /**
     * Extract the downloaded archive into the working directory.
     */
    protected function extract(string $archivePath, string $workingDirectory): void
    {
        if (str_ends_with($archivePath, '.zip')) {
            $this->extractZip($archivePath, $workingDirectory);

            return;
        }

        $this->extractTarGz($archivePath, $workingDirectory);
    }

    /**
     * Extract a zip archive.
     */
    protected function extractZip(string $archivePath, string $workingDirectory): void
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('The PHP zip extension is required to extract Windows binary archives.');
        }

        $zip = new ZipArchive;

        if ($zip->open($archivePath) !== true) {
            throw new RuntimeException("Unable to open zip archive [{$archivePath}].");
        }

        if (! $zip->extractTo($workingDirectory)) {
            $zip->close();

            throw new RuntimeException("Unable to extract zip archive [{$archivePath}].");
        }

        $zip->close();
    }

    /**
     * Extract a gzipped tar archive.
     */
    protected function extractTarGz(string $archivePath, string $workingDirectory): void
    {
        try {
            $phar = new PharData($archivePath);
            $tarPath = substr($archivePath, 0, -3);

            if (! file_exists($tarPath)) {
                $phar->decompress();
            }

            (new PharData($tarPath))->extractTo($workingDirectory, null, true);
        } catch (\Exception $exception) {
            $this->extractWithTar($archivePath, $workingDirectory, $exception);
        }
    }

    /**
     * Extract a gzipped tar archive with the system tar command.
     */
    protected function extractWithTar(string $archivePath, string $workingDirectory, \Throwable $previous): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            throw new RuntimeException("Unable to extract tar archive [{$archivePath}].", 0, $previous);
        }

        $process = new Process(['tar', '-xzf', $archivePath, '-C', $workingDirectory]);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException(
                trim($process->getErrorOutput()) ?: "Unable to extract tar archive [{$archivePath}].",
                0,
                $previous,
            );
        }
    }

    /**
     * Move the extracted binary into the configured location.
     */
    protected function installBinary(string $workingDirectory, string $binaryPath): void
    {
        $source = $this->findExtractedBinary($workingDirectory);

        if ($source === null) {
            throw new RuntimeException('Unable to locate the privacy-filter binary in the extracted archive.');
        }

        $directory = dirname($binaryPath);

        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new RuntimeException("Unable to create binary directory [{$directory}].");
        }

        if (! copy($source, $binaryPath)) {
            throw new RuntimeException("Unable to install privacy-filter binary to [{$binaryPath}].");
        }

        @chmod($binaryPath, 0755);
    }

    /**
     * Find the privacy-filter executable in an extracted archive.
     */
    protected function findExtractedBinary(string $workingDirectory): ?string
    {
        $binaryName = PHP_OS_FAMILY === 'Windows' ? 'privacy-filter.exe' : 'privacy-filter';
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($workingDirectory));

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getFilename() === $binaryName) {
                return $file->getPathname();
            }
        }

        return null;
    }

    /**
     * Delete a directory and all of its contents.
     */
    protected function deleteDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }

        rmdir($directory);
    }
}
