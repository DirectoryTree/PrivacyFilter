<?php

namespace DirectoryTree\PrivacyFilter\Commands;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Number;
use RuntimeException;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class FileDownloader
{
    /**
     * Create a new file downloader instance.
     */
    public function __construct(
        protected OutputInterface $output
    ) {}

    /**
     * Download a file to the given path.
     */
    public function download(string $url, string $target, string $label): void
    {
        $expected = Http::head($url)->header('Content-Length');

        $progress = $this->makeProgressBar($expected, $label);

        try {
            Http::timeout(0)->withOptions([
                'progress' => function (int $total, int $downloaded) use ($progress, $expected) {
                    $progress->setProgress(min($downloaded, $expected));
                },
            ])->sink($target)->get($url)->throw();
        } catch (Throwable $exception) {
            throw new RuntimeException(
                message: "Unable to download {$label} from [{$url}]: {$exception->getMessage()}",
                previous: $exception,
            );
        } finally {
            $progress->finish();

            $this->output->writeln(PHP_EOL);
        }
    }

    /**
     * Create a progress bar for the download.
     */
    protected function makeProgressBar(?int $expectedBytes, string $label): ProgressBar
    {
        $progressBar = new ProgressBar($this->output, $expectedBytes ?? 0);

        $label = 'Downloading '.$label.'...';

        $progressBar->setFormat($expectedBytes
            ? $label.' %current_bytes%/%max_bytes% [%bar%] %percent:3s%%'
            : $label.' %current_bytes% downloaded'
        );

        $progressBar->setPlaceholderFormatterDefinition(
            'current_bytes',
            fn (ProgressBar $bar) => Number::fileSize($bar->getProgress()),
        );

        $progressBar->setPlaceholderFormatterDefinition(
            'max_bytes',
            fn (ProgressBar $bar) => Number::fileSize($bar->getMaxSteps() ?? 0),
        );

        $progressBar->start();

        return $progressBar;
    }
}
