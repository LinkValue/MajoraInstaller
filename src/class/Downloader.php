<?php
require_once '../vendor/autoload.php';
require_once 'Config.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Symfony\Component\Filesystem\Filesystem;
use Distill\Distill;

/**
 * Class Downloader
 */
class Downloader
{

    /**
     * @var Filesystem
     */
    private $fs;

    /**
     * @var Config
     */
    private $config;

    /**
     * Downloader constructor.
     */
    public function __construct()
    {
        $this->fs = new Filesystem();
        $this->config = new Config();
    }

    /**
     * get Zip majora-standard-edition from repository
     * @return \GuzzleHttp\Psr7\Response
     */
    public function initialize($dir)
    {
        $client = new Client();
        echo '... downloading Majora' . "\n";

        try{
            $request = $client->request('GET', $this->config->majora_path);
            $response = $request->getBody();
            echo '... Majora succesfully downloaded' . "\n";
            $this->fs->dumpFile($dir . '/' . $this->config->local_zip_name, $response);
            echo '... Majora succesfully copied' . "\n";
            $distill = new Distill();
            $extractionSucceeded = $distill->extractWithoutRootDirectory($dir . '/' . $this->config->local_zip_name, $dir);
        }
        catch (ClientException $e) {
            throw new \RuntimeException(sprintf(
                "There was an error downloading :  %s",
                $e->getMessage()
            ), $e);
        }
    }
}