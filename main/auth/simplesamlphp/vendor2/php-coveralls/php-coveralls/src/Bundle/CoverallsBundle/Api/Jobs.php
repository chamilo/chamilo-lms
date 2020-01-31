<?php

namespace PhpCoveralls\Bundle\CoverallsBundle\Api;

use PhpCoveralls\Bundle\CoverallsBundle\Collector\CiEnvVarsCollector;
use PhpCoveralls\Bundle\CoverallsBundle\Collector\CloverXmlCoverageCollector;
use PhpCoveralls\Bundle\CoverallsBundle\Collector\GitInfoCollector;
use PhpCoveralls\Bundle\CoverallsBundle\Entity\JsonFile;
use PhpCoveralls\Component\System\Git\GitCommand;

/**
 * Jobs API.
 *
 * @author Kitamura Satoshi <with.no.parachute@gmail.com>
 */
class Jobs extends CoverallsApi
{
    /**
     * URL for jobs API.
     *
     * @var string
     */
    const URL = '/api/v1/jobs';

    /**
     * Filename as a POST parameter.
     *
     * @var string
     */
    const FILENAME = 'json_file';

    /**
     * JsonFile.
     *
     * @var JsonFile
     */
    protected $jsonFile;

    // API

    /**
     * Collect clover XML into json_file.
     *
     * @return $this
     */
    public function collectCloverXml()
    {
        $rootDir = $this->config->getRootDir();
        $cloverXmlPaths = $this->config->getCloverXmlPaths();
        $xmlCollector = new CloverXmlCoverageCollector();

        foreach ($cloverXmlPaths as $cloverXmlPath) {
            $xml = simplexml_load_file($cloverXmlPath);

            $xmlCollector->collect($xml, $rootDir);
        }

        $this->jsonFile = $xmlCollector->getJsonFile();

        if ($this->config->isExcludeNoStatements()) {
            $this->jsonFile->excludeNoStatementsFiles();
        }

        $this->jsonFile->sortSourceFiles();

        return $this;
    }

    /**
     * Collect git repository info into json_file.
     *
     * @return $this
     */
    public function collectGitInfo()
    {
        $command = new GitCommand();
        $gitCollector = new GitInfoCollector($command);

        $this->jsonFile->setGit($gitCollector->collect());

        return $this;
    }

    /**
     * Collect environment variables.
     *
     * @param array $env $_SERVER environment
     *
     * @throws \PhpCoveralls\Bundle\CoverallsBundle\Entity\Exception\RequirementsNotSatisfiedException
     *
     * @return $this
     */
    public function collectEnvVars(array $env)
    {
        $envCollector = new CiEnvVarsCollector($this->config);

        try {
            $this->jsonFile->fillJobs($envCollector->collect($env));
        } catch (\PhpCoveralls\Bundle\CoverallsBundle\Entity\Exception\RequirementsNotSatisfiedException $e) {
            $e->setReadEnv($envCollector->getReadEnv());

            throw $e;
        }

        return $this;
    }

    /**
     * Dump uploading json file.
     *
     * @return $this
     */
    public function dumpJsonFile()
    {
        $jsonPath = $this->config->getJsonPath();

        file_put_contents($jsonPath, $this->jsonFile);

        return $this;
    }

    /**
     * Send json_file to jobs API.
     *
     * @return null|\GuzzleHttp\Psr7\Response
     */
    public function send()
    {
        if ($this->config->isDryRun()) {
            return;
        }

        $url = $this->config->getEntryPoint() . static::URL;
        $jsonPath = $this->config->getJsonPath();

        return $this->upload($url, $jsonPath, static::FILENAME);
    }

    // accessor

    /**
     * Set JsonFile.
     *
     * @param JsonFile $jsonFile json_file content
     *
     * @return $this
     */
    public function setJsonFile(JsonFile $jsonFile)
    {
        $this->jsonFile = $jsonFile;

        return $this;
    }

    /**
     * Return JsonFile.
     *
     * @return null|JsonFile
     */
    public function getJsonFile()
    {
        return $this->jsonFile;
    }

    // internal method

    /**
     * Upload a file.
     *
     * @param string $url      uRL to upload
     * @param string $path     file path
     * @param string $filename filename
     *
     * @return \GuzzleHttp\Psr7\Response
     */
    protected function upload($url, $path, $filename)
    {
        $options = [
            'multipart' => [
                [
                    'name' => $filename,
                    'contents' => file_get_contents($path),
                    'filename' => basename($path),
                ],
            ],
        ];

        return $this->client->post($url, $options);
    }
}
