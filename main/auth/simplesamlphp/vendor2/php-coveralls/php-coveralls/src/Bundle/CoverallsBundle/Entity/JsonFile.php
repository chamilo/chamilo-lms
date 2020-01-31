<?php

namespace PhpCoveralls\Bundle\CoverallsBundle\Entity;

use PhpCoveralls\Bundle\CoverallsBundle\Entity\Exception\RequirementsNotSatisfiedException;
use PhpCoveralls\Bundle\CoverallsBundle\Entity\Git\Git;
use PhpCoveralls\Bundle\CoverallsBundle\Version;

/**
 * Data represents "json_file" of Coveralls API.
 *
 * @author Kitamura Satoshi <with.no.parachute@gmail.com>
 */
class JsonFile extends Coveralls
{
    /**
     * Service name.
     *
     * @var null|string
     */
    protected $serviceName;

    /**
     * Service job id.
     *
     * @var null|string
     */
    protected $serviceJobId;

    /**
     * Service number (not documented).
     *
     * @var string
     */
    protected $serviceNumber;

    /**
     * Service event type (not documented).
     *
     * @var string
     */
    protected $serviceEventType;

    /**
     * Build URL of the project (not documented).
     *
     * @var string
     */
    protected $serviceBuildUrl;

    /**
     * Branch name (not documented).
     *
     * @var string
     */
    protected $serviceBranch;

    /**
     * Pull request info (not documented).
     *
     * @var string
     */
    protected $servicePullRequest;

    /**
     * Repository token.
     *
     * @var null|string
     */
    protected $repoToken;

    /**
     * Source files.
     *
     * @var \PhpCoveralls\Bundle\CoverallsBundle\Entity\SourceFile[]
     */
    protected $sourceFiles = [];

    /**
     * Git data.
     *
     * @var null|Git
     */
    protected $git;

    /**
     * A timestamp when the job ran. Must be parsable by Ruby.
     *
     * "2013-02-18 00:52:48 -0800"
     *
     * @var null|string
     */
    protected $runAt;

    /**
     * Metrics.
     *
     * @var Metrics
     */
    protected $metrics;

    // API

    /**
     * {@inheritdoc}
     *
     * @see \PhpCoveralls\Bundle\CoverallsBundle\Entity\ArrayConvertable::toArray()
     */
    public function toArray()
    {
        $array = [];

        $arrayMap = [
            // json key => property name
            'service_name' => 'serviceName',
            'service_job_id' => 'serviceJobId',
            'service_number' => 'serviceNumber',
            'service_build_url' => 'serviceBuildUrl',
            'service_branch' => 'serviceBranch',
            'service_pull_request' => 'servicePullRequest',
            'service_event_type' => 'serviceEventType',
            'repo_token' => 'repoToken',
            'git' => 'git',
            'run_at' => 'runAt',
            'source_files' => 'sourceFiles',
        ];

        foreach ($arrayMap as $jsonKey => $propName) {
            if (isset($this->$propName)) {
                $array[$jsonKey] = $this->toJsonProperty($this->$propName);
            }
        }

        $array['environment'] = [
            'packagist_version' => Version::VERSION,
        ];

        return $array;
    }

    /**
     * Fill environment variables.
     *
     * @param array $env $_SERVER environment
     *
     * @throws \RuntimeException
     *
     * @return $this
     */
    public function fillJobs(array $env)
    {
        return $this
            ->fillStandardizedEnvVars($env)
            ->ensureJobs();
    }

    /**
     * Exclude source files that have no executable statements.
     */
    public function excludeNoStatementsFiles()
    {
        $this->sourceFiles = array_filter(
            $this->sourceFiles,
            function (SourceFile $sourceFile) {
                return $sourceFile->getMetrics()->hasStatements();
            }
        );
    }

    /**
     * Sort source files by path.
     */
    public function sortSourceFiles()
    {
        ksort($this->sourceFiles);
    }

    /**
     * Return line coverage.
     *
     * @return float
     */
    public function reportLineCoverage()
    {
        $metrics = $this->getMetrics();

        foreach ($this->sourceFiles as $sourceFile) {
            /* @var $sourceFile \PhpCoveralls\Bundle\CoverallsBundle\Entity\SourceFile */
            $metrics->merge($sourceFile->getMetrics());
        }

        return $metrics->getLineCoverage();
    }

    // accessor

    /**
     * Return whether the json file has source file.
     *
     * @param string $path absolute path to source file
     *
     * @return bool
     */
    public function hasSourceFile($path)
    {
        return isset($this->sourceFiles[$path]);
    }

    /**
     * Return source file.
     *
     * @param string $path absolute path to source file
     *
     * @return null|\PhpCoveralls\Bundle\CoverallsBundle\Entity\SourceFile
     */
    public function getSourceFile($path)
    {
        if ($this->hasSourceFile($path)) {
            return $this->sourceFiles[$path];
        }
    }

    /**
     * Add source file.
     *
     * @param SourceFile $sourceFile
     */
    public function addSourceFile(SourceFile $sourceFile)
    {
        $this->sourceFiles[$sourceFile->getPath()] = $sourceFile;
    }

    /**
     * Return whether the json file has a source file.
     *
     * @return bool
     */
    public function hasSourceFiles()
    {
        return count($this->sourceFiles) > 0;
    }

    /**
     * Return source files.
     *
     * @return \PhpCoveralls\Bundle\CoverallsBundle\Entity\SourceFile[]
     */
    public function getSourceFiles()
    {
        return $this->sourceFiles;
    }

    /**
     * Set service name.
     *
     * @param string $serviceName service name
     *
     * @return $this
     */
    public function setServiceName($serviceName)
    {
        $this->serviceName = $serviceName;

        return $this;
    }

    /**
     * Return service name.
     *
     * @return null|string
     */
    public function getServiceName()
    {
        return $this->serviceName;
    }

    /**
     * Set repository token.
     *
     * @param string $repoToken repository token
     *
     * @return $this
     */
    public function setRepoToken($repoToken)
    {
        $this->repoToken = $repoToken;

        return $this;
    }

    /**
     * Return repository token.
     *
     * @return null|string
     */
    public function getRepoToken()
    {
        return $this->repoToken;
    }

    /**
     * Set service job id.
     *
     * @param string $serviceJobId service job id
     *
     * @return $this
     */
    public function setServiceJobId($serviceJobId)
    {
        $this->serviceJobId = $serviceJobId;

        return $this;
    }

    /**
     * Return service job id.
     *
     * @return null|string
     */
    public function getServiceJobId()
    {
        return $this->serviceJobId;
    }

    /**
     * Return service number.
     *
     * @return string
     */
    public function getServiceNumber()
    {
        return $this->serviceNumber;
    }

    /**
     * Return service event type.
     *
     * @return string
     */
    public function getServiceEventType()
    {
        return $this->serviceEventType;
    }

    /**
     * Return build URL of the project.
     *
     * @return string
     */
    public function getServiceBuildUrl()
    {
        return $this->serviceBuildUrl;
    }

    /**
     * Return branch name.
     *
     * @return string
     */
    public function getServiceBranch()
    {
        return $this->serviceBranch;
    }

    /**
     * Return pull request info.
     *
     * @return string
     */
    public function getServicePullRequest()
    {
        return $this->servicePullRequest;
    }

    /**
     * Set git data.
     *
     * @param Git $git git data
     *
     * @return $this
     */
    public function setGit(Git $git)
    {
        $this->git = $git;

        return $this;
    }

    /**
     * Return git data.
     *
     * @return null|Git
     */
    public function getGit()
    {
        return $this->git;
    }

    /**
     * Set timestamp when the job ran.
     *
     * @param string $runAt timestamp
     *
     * @return $this
     */
    public function setRunAt($runAt)
    {
        $this->runAt = $runAt;

        return $this;
    }

    /**
     * Return timestamp when the job ran.
     *
     * @return null|string
     */
    public function getRunAt()
    {
        return $this->runAt;
    }

    /**
     * Return metrics.
     *
     * @return \PhpCoveralls\Bundle\CoverallsBundle\Entity\Metrics
     */
    public function getMetrics()
    {
        if ($this->metrics === null) {
            $this->metrics = new Metrics();
        }

        return $this->metrics;
    }

    // internal method

    /**
     * Convert to json property.
     *
     * @param mixed $prop
     *
     * @return mixed
     */
    protected function toJsonProperty($prop)
    {
        if ($prop instanceof Coveralls) {
            return $prop->toArray();
        }

        if (is_array($prop)) {
            return $this->toJsonPropertyArray($prop);
        }

        return $prop;
    }

    /**
     * Convert to array as json property.
     *
     * @param array $propArray
     *
     * @return array
     */
    protected function toJsonPropertyArray(array $propArray)
    {
        $array = [];

        foreach ($propArray as $prop) {
            $array[] = $this->toJsonProperty($prop);
        }

        return $array;
    }

    /**
     * Fill standardized environment variables.
     *
     * "CI_NAME", "CI_BUILD_NUMBER" must be set.
     *
     * Env vars are:
     *
     * * CI_NAME
     * * CI_BUILD_NUMBER
     * * CI_BUILD_URL
     * * CI_BRANCH
     * * CI_PULL_REQUEST
     *
     * These vars are supported by Codeship.
     *
     * @param array $env $_SERVER environment
     *
     * @return $this
     */
    protected function fillStandardizedEnvVars(array $env)
    {
        $map = [
            // defined in Ruby lib
            'serviceName' => 'CI_NAME',
            'serviceNumber' => 'CI_BUILD_NUMBER',
            'serviceBuildUrl' => 'CI_BUILD_URL',
            'serviceBranch' => 'CI_BRANCH',
            'servicePullRequest' => 'CI_PULL_REQUEST',

            // extends by php-coveralls
            'serviceJobId' => 'CI_JOB_ID',
            'serviceEventType' => 'COVERALLS_EVENT_TYPE',
            'repoToken' => 'COVERALLS_REPO_TOKEN',
        ];

        foreach ($map as $propName => $envName) {
            if (isset($env[$envName])) {
                $this->$propName = $env[$envName];
            }
        }

        return $this;
    }

    /**
     * Ensure data consistency for jobs API.
     *
     * @throws \RuntimeException
     *
     * @return $this
     */
    protected function ensureJobs()
    {
        if (!$this->hasSourceFiles()) {
            throw new \RuntimeException('source_files must be set');
        }

        if ($this->requireServiceJobId()) {
            return $this;
        }

        if ($this->requireServiceNumber()) {
            return $this;
        }

        if ($this->requireServiceEventType()) {
            return $this;
        }

        if ($this->requireRepoToken()) {
            return $this;
        }

        if ($this->isUnsupportedServiceJob()) {
            return $this;
        }

        throw new RequirementsNotSatisfiedException();
    }

    /**
     * Return whether the job requires "service_job_id" (for Travis CI).
     *
     * @return bool
     */
    protected function requireServiceJobId()
    {
        return $this->serviceName !== null && $this->serviceJobId !== null && $this->repoToken === null;
    }

    /**
     * Return whether the job requires "service_number" (for CircleCI, Jenkins, Codeship or other CIs).
     *
     * @return bool
     */
    protected function requireServiceNumber()
    {
        return $this->serviceName !== null && $this->serviceNumber !== null && $this->repoToken !== null;
    }

    /**
     * Return whether the job requires "service_event_type" (for local environment).
     *
     * @return bool
     */
    protected function requireServiceEventType()
    {
        return $this->serviceName !== null && $this->serviceEventType !== null && $this->repoToken !== null;
    }

    /**
     * Return whether the job requires "repo_token" (for Travis PRO).
     *
     * @return bool
     */
    protected function requireRepoToken()
    {
        return $this->serviceName === 'travis-pro' && $this->repoToken !== null;
    }

    /**
     * Return whether the job is running on unsupported service.
     *
     * @return bool
     */
    protected function isUnsupportedServiceJob()
    {
        return $this->serviceJobId === null && $this->serviceNumber === null && $this->serviceEventType === null && $this->repoToken !== null;
    }
}
