<?php

namespace PhpCoveralls\Bundle\CoverallsBundle\Repository;

use GuzzleHttp\Psr7\Response;
use PhpCoveralls\Bundle\CoverallsBundle\Api\Jobs;
use PhpCoveralls\Bundle\CoverallsBundle\Config\Configuration;
use PhpCoveralls\Bundle\CoverallsBundle\Entity\JsonFile;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

/**
 * Jobs API client.
 *
 * Just wrap for logging.
 *
 * @author Kitamura Satoshi <with.no.parachute@gmail.com>
 */
class JobsRepository implements LoggerAwareInterface
{
    /**
     * Jobs API.
     *
     * @var \PhpCoveralls\Bundle\CoverallsBundle\Api\Jobs
     */
    protected $api;

    /**
     * Configuration.
     *
     * @var \PhpCoveralls\Bundle\CoverallsBundle\Config\Configuration
     */
    protected $config;

    /**
     * Logger.
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * Constructor.
     *
     * @param Jobs          $api    aPI
     * @param Configuration $config configuration
     */
    public function __construct(Jobs $api, Configuration $config)
    {
        $this->api = $api;
        $this->config = $config;
    }

    // API

    /**
     * Persist coverage data to Coveralls.
     *
     * @return bool
     */
    public function persist()
    {
        try {
            return $this
                ->collectCloverXml()
                ->collectGitInfo()
                ->collectEnvVars()
                ->dumpJsonFile()
                ->send();
        } catch (\PhpCoveralls\Bundle\CoverallsBundle\Entity\Exception\RequirementsNotSatisfiedException $e) {
            $this->logger->error(sprintf('%s', $e->getHelpMessage()));

            return false;
        } catch (\Exception $e) {
            $this->logger->error(sprintf("%s\n\n%s", $e->getMessage(), $e->getTraceAsString()));

            return false;
        }
    }

    // LoggerAwareInterface

    /**
     * {@inheritdoc}
     *
     * @see \Psr\Log\LoggerAwareInterface::setLogger()
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    // internal method

    /**
     * Collect clover XML into json_file.
     *
     * @return $this
     */
    protected function collectCloverXml()
    {
        $this->logger->info('Load coverage clover log:');

        foreach ($this->config->getCloverXmlPaths() as $path) {
            $this->logger->info(sprintf('  - %s', $path));
        }

        $jsonFile = $this->api->collectCloverXml()->getJsonFile();

        if ($jsonFile->hasSourceFiles()) {
            $this->logCollectedSourceFiles($jsonFile);
        }

        return $this;
    }

    /**
     * Collect git repository info into json_file.
     *
     * @return $this
     */
    protected function collectGitInfo()
    {
        $this->logger->info('Collect git info');

        $this->api->collectGitInfo();

        return $this;
    }

    /**
     * Collect environment variables.
     *
     * @return $this
     */
    protected function collectEnvVars()
    {
        $this->logger->info('Read environment variables');

        $this->api->collectEnvVars($_SERVER);

        return $this;
    }

    /**
     * Dump submitting json file.
     *
     * @return $this
     */
    protected function dumpJsonFile()
    {
        $jsonPath = $this->config->getJsonPath();
        $this->logger->info(sprintf('Dump submitting json file: %s', $jsonPath));

        $this->api->dumpJsonFile();

        $filesize = number_format(filesize($jsonPath) / 1024, 2); // kB
        $this->logger->info(sprintf('File size: <info>%s</info> kB', $filesize));

        return $this;
    }

    /**
     * Send json_file to Jobs API.
     *
     * @return bool
     */
    protected function send()
    {
        $this->logger->info(sprintf('Submitting to %s', $this->config->getEntryPoint() . Jobs::URL));

        try {
            $response = $this->api->send();
            $message = $response
                ? sprintf('Finish submitting. status: %s %s', $response->getStatusCode(), $response->getReasonPhrase())
                : 'Finish dry run';

            $this->logger->info($message);

            if ($response instanceof Response) {
                $this->logResponse($response);
            }

            return true;
        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            // connection error
            $message = sprintf("Connection error occurred. %s\n\n%s", $e->getMessage(), $e->getTraceAsString());
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            // 422 Unprocessable Entity
            $response = $e->getResponse();
            $message = sprintf('Client error occurred. status: %s %s', $response->getStatusCode(), $response->getReasonPhrase());
        } catch (\GuzzleHttp\Exception\ServerException $e) {
            // 500 Internal Server Error
            // 503 Service Unavailable
            $response = $e->getResponse();
            $message = sprintf('Server error occurred. status: %s %s', $response->getStatusCode(), $response->getReasonPhrase());
        }

        $this->logger->error($message);

        if (isset($response)) {
            $this->logResponse($response);
        }

        return false;
    }

    // logging

    /**
     * Colorize coverage.
     *
     * * green  90% - 100% <info>
     * * yellow 80% -  90% <comment>
     * * red     0% -  80% <fg=red>
     *
     * @param float  $coverage coverage
     * @param string $format   format string to colorize
     *
     * @return string
     */
    protected function colorizeCoverage($coverage, $format)
    {
        if ($coverage >= 90) {
            return sprintf('<info>%s</info>', $format);
        }

        if ($coverage >= 80) {
            return sprintf('<comment>%s</comment>', $format);
        }

        return sprintf('<fg=red>%s</fg=red>', $format);
    }

    /**
     * Log collected source files.
     *
     * @param JsonFile $jsonFile json file
     */
    protected function logCollectedSourceFiles(JsonFile $jsonFile)
    {
        $sourceFiles = $jsonFile->getSourceFiles();
        $numFiles = count($sourceFiles);

        $this->logger->info(sprintf('Found <info>%s</info> source file%s:', number_format($numFiles), $numFiles > 1 ? 's' : ''));

        foreach ($sourceFiles as $sourceFile) {
            /* @var $sourceFile \PhpCoveralls\Bundle\CoverallsBundle\Entity\SourceFile */
            $coverage = $sourceFile->reportLineCoverage();
            $template = '  - ' . $this->colorizeCoverage($coverage, '%6.2f%%') . ' %s';

            $this->logger->info(sprintf($template, $coverage, $sourceFile->getName()));
        }

        $coverage = $jsonFile->reportLineCoverage();
        $template = 'Coverage: ' . $this->colorizeCoverage($coverage, '%6.2f%% (%d/%d)');
        $metrics = $jsonFile->getMetrics();

        $this->logger->info(sprintf($template, $coverage, $metrics->getCoveredStatements(), $metrics->getStatements()));
    }

    /**
     * Log response.
     *
     * @param Response $response aPI response
     */
    protected function logResponse(Response $response)
    {
        $raw_body = (string) $response->getBody();
        $body = json_decode($raw_body, true);
        if ($body === null) {
            // the response body is not in JSON format
            $this->logger->error($raw_body);
        } elseif (isset($body['error'])) {
            if (isset($body['message'])) {
                $this->logger->error($body['message']);
            }
        } else {
            if (isset($body['message'])) {
                $this->logger->info(sprintf('Accepted %s', $body['message']));
            }

            if (isset($body['url'])) {
                $this->logger->info(sprintf('You can see the build on %s', $body['url']));
            }
        }
    }
}
