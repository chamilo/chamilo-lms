<?php

namespace PhpCoveralls\Tests\Bundle\CoverallsBundle\Repository;

use PhpCoveralls\Bundle\CoverallsBundle\Api\Jobs;
use PhpCoveralls\Bundle\CoverallsBundle\Config\Configuration;
use PhpCoveralls\Bundle\CoverallsBundle\Entity\Exception\RequirementsNotSatisfiedException;
use PhpCoveralls\Bundle\CoverallsBundle\Entity\JsonFile;
use PhpCoveralls\Bundle\CoverallsBundle\Entity\Metrics;
use PhpCoveralls\Bundle\CoverallsBundle\Entity\SourceFile;
use PhpCoveralls\Bundle\CoverallsBundle\Repository\JobsRepository;
use PhpCoveralls\Tests\ProjectTestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * @covers \PhpCoveralls\Bundle\CoverallsBundle\Repository\JobsRepository
 *
 * @author Kitamura Satoshi <with.no.parachute@gmail.com>
 */
class JobsRepositoryTest extends ProjectTestCase
{
    protected function setUp()
    {
        $this->setUpDir(realpath(__DIR__ . '/../../..'));
    }

    // persist()

    /**
     * @test
     */
    public function shouldPersist()
    {
        $statusCode = 200;
        $url = 'https://coveralls.io/jobs/67528';
        $response = new \GuzzleHttp\Psr7\Response(
            $statusCode,
            [],
            json_encode([
                'message' => 'Job #115.3',
                'url' => $url,
            ]),
            '1.1',
            'OK'
        );
        $api = $this->createApiMock($response, $statusCode, $url);
        $config = $this->createConfiguration();
        $logger = $this->createLoggerMock();

        $object = new JobsRepository($api, $config);

        $object->setLogger($logger);
        self::assertTrue($object->persist());
    }

    /**
     * @test
     */
    public function shouldPersistDryRun()
    {
        $api = $this->createApiMock(null, 200); // params means API won't crash, but it will return null, as it does for dry-run
        $config = $this->createConfiguration();
        $logger = $this->createLoggerMock();

        $object = new JobsRepository($api, $config);

        $object->setLogger($logger);
        self::assertTrue($object->persist());
    }

    // unexpected Exception
    // source files not found

    /**
     * @test
     */
    public function unexpectedException()
    {
        $api = $this->createApiMockWithException();
        $config = $this->createConfiguration();
        $logger = $this->createLoggerMock();

        $object = new JobsRepository($api, $config);

        $object->setLogger($logger);
        self::assertFalse($object->persist());
    }

    /**
     * @test
     */
    public function requirementsNotSatisfiedException()
    {
        $api = $this->createApiMockWithRequirementsNotSatisfiedException();
        $config = $this->createConfiguration();
        $logger = $this->createLoggerMock();

        $object = new JobsRepository($api, $config);

        $object->setLogger($logger);
        self::assertFalse($object->persist());
    }

    // curl error

    /**
     * @test
     */
    public function networkDisconnected()
    {
        $api = $this->createApiMock(null, null);
        $config = $this->createConfiguration();
        $logger = $this->createLoggerMock();

        $object = new JobsRepository($api, $config);

        $object->setLogger($logger);
        self::assertFalse($object->persist());
    }

    // response 422

    /**
     * @test
     */
    public function response422()
    {
        $statusCode = 422;
        $response = new \GuzzleHttp\Psr7\Response(
            $statusCode,
            [],
            json_encode([
                'message' => 'Build processing error.',
                'url' => '',
                'error' => true,
            ]),
            '1.1',
            'Unprocessable Entity'
        );
        $api = $this->createApiMock($response, $statusCode);
        $config = $this->createConfiguration();
        $logger = $this->createLoggerMock();

        $object = new JobsRepository($api, $config);

        $object->setLogger($logger);
        self::assertFalse($object->persist());
    }

    // response 500

    /**
     * @test
     */
    public function response500()
    {
        $statusCode = 500;
        $response = new \GuzzleHttp\Psr7\Response($statusCode, [], null, '1.1', 'Internal Server Error');
        $api = $this->createApiMock($response, $statusCode);
        $config = $this->createConfiguration();
        $logger = $this->createLoggerMock();

        $object = new JobsRepository($api, $config);

        $object->setLogger($logger);
        self::assertFalse($object->persist());
    }

    /**
     * @return Jobs
     */
    protected function createApiMockWithRequirementsNotSatisfiedException()
    {
        $api = $this->prophesize(Jobs::class);
        $this->setUpJobsApiWithCollectCloverXmlThrow($api, new RequirementsNotSatisfiedException());
        $this->setUpJobsApiWithGetJsonFileNotCalled($api);
        $this->setUpJobsApiWithCollectGitInfoNotCalled($api);
        $this->setUpJobsApiWithCollectEnvVarsNotCalled($api);
        $this->setUpJobsApiWithDumpJsonFileNotCalled($api);
        $this->setUpJobsApiWithSendNotCalled($api);

        return $api->reveal();
    }

    /**
     * @return Jobs
     */
    protected function createApiMockWithException()
    {
        $api = $this->prophesize(Jobs::class);
        $this->setUpJobsApiWithCollectCloverXmlThrow($api, new \Exception('unexpected exception'));
        $this->setUpJobsApiWithGetJsonFileNotCalled($api);
        $this->setUpJobsApiWithCollectGitInfoNotCalled($api);
        $this->setUpJobsApiWithCollectEnvVarsNotCalled($api);
        $this->setUpJobsApiWithDumpJsonFileNotCalled($api);
        $this->setUpJobsApiWithSendNotCalled($api);

        return $api->reveal();
    }

    /**
     * @param null|ResponseInterface $response
     * @param null|int               $statusCode
     * @param string                 $uri
     *
     * @return Jobs
     */
    protected function createApiMock($response, $statusCode = null, $uri = '/')
    {
        $api = $this->prophesize(Jobs::class);
        $this->setUpJobsApiWithCollectCloverXmlCalled($api);
        $this->setUpJobsApiWithGetJsonFileCalled($api, $this->createJsonFile());
        $this->setUpJobsApiWithCollectGitInfoCalled($api);
        $this->setUpJobsApiWithCollectEnvVarsCalled($api);
        $this->setUpJobsApiWithDumpJsonFileCalled($api);
        $this->setUpJobsApiWithSendCalled($api, $statusCode, new \GuzzleHttp\Psr7\Request('POST', $uri), $response);

        return $api->reveal();
    }

    /**
     * @return LoggerInterface
     */
    protected function createLoggerMock()
    {
        $logger = $this->prophesize(NullLogger::class);
        $logger->info();
        $logger->error();

        return $logger->reveal();
    }

    // dependent object

    /**
     * @param int $percent
     *
     * @return array
     */
    protected function createCoverage($percent)
    {
        // percent = (covered / stmt) * 100;
        // (percent * stmt) / 100 = covered
        $stmt = 100;
        $covered = ($percent * $stmt) / 100;
        $coverage = array_fill(0, 100, 0);

        for ($i = 0; $i < $covered; ++$i) {
            $coverage[$i] = 1;
        }

        return $coverage;
    }

    protected function createJsonFile()
    {
        $jsonFile = new JsonFile();

        $repositoryTestDir = $this->srcDir . '/RepositoryTest';

        $sourceFiles = [
            0 => new SourceFile($repositoryTestDir . '/Coverage0.php', 'Coverage0.php'),
            10 => new SourceFile($repositoryTestDir . '/Coverage10.php', 'Coverage10.php'),
            70 => new SourceFile($repositoryTestDir . '/Coverage70.php', 'Coverage70.php'),
            80 => new SourceFile($repositoryTestDir . '/Coverage80.php', 'Coverage80.php'),
            90 => new SourceFile($repositoryTestDir . '/Coverage90.php', 'Coverage90.php'),
            100 => new SourceFile($repositoryTestDir . '/Coverage100.php', 'Coverage100.php'),
        ];

        foreach ($sourceFiles as $percent => $sourceFile) {
            $sourceFile->getMetrics()->merge(new Metrics($this->createCoverage($percent)));
            $jsonFile->addSourceFile($sourceFile);
        }

        return $jsonFile;
    }

    protected function createConfiguration()
    {
        $config = new Configuration();

        return $config->addCloverXmlPath($this->cloverXmlPath);
    }

    // mock

    /**
     * @param Jobs $api
     */
    private function setUpJobsApiWithCollectCloverXmlCalled($api)
    {
        $api
            ->collectCloverXml()
            ->will(function () {
                return $this;
            })
            ->shouldBeCalled();
    }

    /**
     * @param Jobs       $api
     * @param \Exception $exception
     */
    private function setUpJobsApiWithCollectCloverXmlThrow($api, $exception)
    {
        $api
            ->collectCloverXml()
            ->willThrow($exception)
            ->shouldBeCalled();
    }

    /**
     * @param Jobs     $api
     * @param JsonFile $jsonFile
     */
    private function setUpJobsApiWithGetJsonFileCalled($api, $jsonFile)
    {
        $api
            ->getJsonFile()
            ->willReturn($jsonFile)
            ->shouldBeCalled();
    }

    /**
     * @param Jobs $api
     */
    private function setUpJobsApiWithGetJsonFileNotCalled($api)
    {
        $api
            ->getJsonFile()
            ->shouldNotBeCalled();
    }

    /**
     * @param Jobs $api
     */
    private function setUpJobsApiWithCollectGitInfoCalled($api)
    {
        $api
            ->collectGitInfo()
            ->will(function () {
                return $this;
            })
            ->shouldBeCalled();
    }

    /**
     * @param Jobs $api
     */
    private function setUpJobsApiWithCollectGitInfoNotCalled($api)
    {
        $api
            ->collectGitInfo()
            ->shouldNotBeCalled();
    }

    /**
     * @param Jobs $api
     */
    private function setUpJobsApiWithCollectEnvVarsCalled($api)
    {
        $api
            ->collectEnvVars($_SERVER)
            ->will(function () {
                return $this;
            })
            ->shouldBeCalled();
    }

    /**
     * @param Jobs $api
     */
    private function setUpJobsApiWithCollectEnvVarsNotCalled($api)
    {
        $api
            ->collectEnvVars()
            ->shouldNotBeCalled();
    }

    /**
     * @param Jobs $api
     */
    private function setUpJobsApiWithDumpJsonFileCalled($api)
    {
        $api
            ->dumpJsonFile()
            ->will(function () {
                return $this;
            })
            ->shouldBeCalled();
    }

    /**
     * @param Jobs $api
     */
    private function setUpJobsApiWithDumpJsonFileNotCalled($api)
    {
        $api
            ->dumpJsonFile()
            ->shouldNotBeCalled();
    }

    /**
     * @param Jobs                   $api
     * @param null|int               $statusCode
     * @param RequestInterface       $request
     * @param null|ResponseInterface $response
     */
    private function setUpJobsApiWithSendCalled($api, $statusCode, $request, $response)
    {
        if ($statusCode === 200) {
            $api
                ->send()
                ->willReturn($response)
                ->shouldBeCalled();
        } else {
            if ($statusCode === null) {
                $exception = \GuzzleHttp\Exception\ConnectException::create($request);
            } elseif ($statusCode === 422) {
                $exception = \GuzzleHttp\Exception\ClientException::create($request, $response);
            } else {
                $exception = \GuzzleHttp\Exception\ServerException::create($request, $response);
            }

            $api
                ->send()
                ->willThrow($exception)
                ->shouldBeCalled();
        }
    }

    /**
     * @param Jobs $api
     */
    private function setUpJobsApiWithSendNotCalled($api)
    {
        $api
            ->send()
            ->shouldNotBeCalled();
    }
}
