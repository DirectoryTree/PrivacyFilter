<?php

namespace DirectoryTree\PrivacyFilter\Commands;

use FilesystemIterator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
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
            $asset = $this->getAssetName();
            $url = $this->option('url') ?: $this->getReleaseUrl($repository, $release, $asset);
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Installing privacy-filter binary.');
        $this->line("Target: {$binaryPath}");

        if (File::exists($binaryPath) && ! $this->option('force')) {
            $this->warn('The privacy-filter binary already exists. Use --force to overwrite it.');

            return self::SUCCESS;
        }

        $workingDirectory = null;

        try {
            $workingDirectory = $this->getWorkingDirectory();

            $archivePath = $workingDirectory.DIRECTORY_SEPARATOR.$this->getArchiveFileName($url, $asset);

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

        if (! is_string($path) || $path === '') {
            return $fallback;
        }

        return basename($path) ?: $fallback;
    }

    /**
     * Create a temporary working directory.
     */
    protected function getWorkingDirectory(): string
    {
        $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.'privacy-filter-'.bin2hex(random_bytes(8));

        File::ensureDirectoryExists($path);

        return $path;
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
        if (PHP_OS_FAMILY !== 'Windows' && $this->extractWithTar($archivePath, $workingDirectory)) {
            return;
        }

        try {
            $phar = new PharData($archivePath);
            $tarPath = substr($archivePath, 0, -3);

            if (! File::exists($tarPath)) {
                $phar->decompress();
            }

            (new PharData($tarPath))->extractTo($workingDirectory, null, true);
        } catch (\Exception $exception) {
            throw new RuntimeException("Unable to extract tar archive [{$archivePath}].", 0, $exception);
        }
    }

    /**
     * Extract a gzipped tar archive with the system tar command.
     */
    protected function extractWithTar(string $archivePath, string $workingDirectory): bool
    {
        $process = new Process(['tar', '-xzf', $archivePath, '-C', $workingDirectory]);
        $process->run();

        return $process->isSuccessful();
    }

    /**
     * Move the extracted package into the configured location.
     */
    protected function installBinary(string $workingDirectory, string $binaryPath): void
    {
        $source = $this->findExtractedBinary($workingDirectory);

        if ($source === null) {
            throw new RuntimeException('Unable to locate the privacy-filter binary in the extracted archive.');
        }

        $packageDirectory = dirname(dirname($source));
        $installDirectory = $this->installDirectory($binaryPath);

        $this->copyDirectory($packageDirectory, $installDirectory);

        File::chmod($binaryPath, 0755);
    }

    /**
     * Resolve the package installation directory from the configured binary path.
     */
    protected function installDirectory(string $binaryPath): string
    {
        $directory = dirname($binaryPath);

        return basename($directory) === 'bin' ? dirname($directory) : $directory;
    }

    /**
     * Copy a directory and all of its contents.
     */
    protected function copyDirectory(string $source, string $destination): void
    {
        File::ensureDirectoryExists($destination);

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST,
        );

        foreach ($iterator as $file) {
            $target = $destination.DIRECTORY_SEPARATOR.$iterator->getSubPathName();

            if ($file->isLink()) {
                if (File::exists($target) || is_link($target)) {
                    File::delete($target);
                }

                if (! symlink(readlink($file->getPathname()), $target)) {
                    throw new RuntimeException("Unable to create symlink [{$target}].");
                }

                continue;
            }

            if ($file->isDir()) {
                File::ensureDirectoryExists($target);

                continue;
            }

            if (File::exists($target) || is_link($target)) {
                File::delete($target);
            }

            if (! File::copy($file->getPathname(), $target)) {
                throw new RuntimeException("Unable to copy file [{$file->getPathname()}] to [{$target}].");
            }

            File::chmod($target, $file->getPerms() & 0777);
        }
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
        if (! File::isDirectory($directory)) {
            return;
        }

        File::deleteDirectory($directory);
    }
}
