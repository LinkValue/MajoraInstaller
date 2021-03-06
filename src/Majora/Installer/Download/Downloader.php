<?php

namespace Majora\Installer\Download;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\RequestOptions;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class Downloader.
 *
 * @author LinkValue <contact@link-value.fr>
 */
class Downloader
{
    /**
     * @var string
     */
    private $url;

    /**
     * @var string
     */
    private $destinationFile;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var bool
     */
    private $downloaded = false;

    /**
     * @var ProgressBar
     */
    private $progressBar;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * Constructor.
     *
     * @param $url
     * @param $destinationFile
     * @param OutputInterface|null $output
     */
    public function __construct($url, $destinationFile, OutputInterface $output = null)
    {
        $this->url = $url;
        $this->destinationFile = $destinationFile;
        $this->output = $output;
        $this->filesystem = new Filesystem();
    }

    /**
     * @return bool
     */
    public function download()
    {
        if ($this->isDownloaded()) {
            return true;
        }

        $httpClient = new Client();
        $request = new Request('GET', $this->getUrl());
        $response = $httpClient->send($request, [
            RequestOptions::PROGRESS => function ($total, $current) {
                if ($total <= 0 || !$this->output) {
                    return;
                }

                if (!$this->progressBar) {
                    $this->progressBar = new ProgressBar($this->output, $total);
                    $this->progressBar->setPlaceholderFormatterDefinition('max', function (ProgressBar $bar) {
                        return $this->formatSize($bar->getMaxSteps());
                    });
                    $this->progressBar->setPlaceholderFormatterDefinition('current', function (ProgressBar $bar) {
                        return str_pad($this->formatSize($bar->getProgress()), 11, ' ', STR_PAD_LEFT);
                    });
                }
                $this->progressBar->setProgress($current);
            },
        ]);

        $this->filesystem->dumpFile($this->getDestinationFile(), $response->getBody()->getContents());

        $this->downloaded = true;

        return true;
    }

    /**
     * Deletes the destination file downloaded.
     */
    public function deleteDestinationFile()
    {
        $this->filesystem->remove($this->getDestinationFile());
    }

    /**
     * Utility method to show the number of bytes in a readable format.
     *
     * @param int $bytes The number of bytes to format
     *
     * @return string The human readable string of bytes (e.g. 4.32MB)
     *
     * @link https://github.com/symfony/symfony-installer/blob/master/src/Symfony/Installer/DownloadCommand.php
     */
    protected function formatSize($bytes)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');

        $bytes = max($bytes, 0);
        $pow = $bytes ? floor(log($bytes, 1024)) : 0;
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return number_format($bytes, 2).' '.$units[$pow];
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @return string
     */
    public function getDestinationFile()
    {
        return $this->destinationFile;
    }

    /**
     * @return bool
     */
    public function isDownloaded()
    {
        return $this->downloaded;
    }
}
