<?php

namespace PhpCoveralls\Tests\Bundle\CoverallsBundle\Entity;

use PhpCoveralls\Bundle\CoverallsBundle\Collector\CloverXmlCoverageCollector;
use PhpCoveralls\Bundle\CoverallsBundle\Entity\Git\Commit;
use PhpCoveralls\Bundle\CoverallsBundle\Entity\Git\Git;
use PhpCoveralls\Bundle\CoverallsBundle\Entity\Git\Remote;
use PhpCoveralls\Bundle\CoverallsBundle\Entity\JsonFile;
use PhpCoveralls\Bundle\CoverallsBundle\Entity\SourceFile;
use PhpCoveralls\Bundle\CoverallsBundle\Version;
use PhpCoveralls\Tests\ProjectTestCase;

/**
 * @covers \PhpCoveralls\Bundle\CoverallsBundle\Entity\JsonFile
 * @covers \PhpCoveralls\Bundle\CoverallsBundle\Entity\Coveralls
 *
 * @author Kitamura Satoshi <with.no.parachute@gmail.com>
 */
class JsonFileTest extends ProjectTestCase
{
    /**
     * @var JsonFile
     */
    private $object;

    protected function setUp()
    {
        $this->setUpDir(realpath(__DIR__ . '/../../..'));

        $this->object = new JsonFile();
    }

    // hasSourceFile()
    // getSourceFile()

    /**
     * @test
     */
    public function shouldNotHaveSourceFileOnConstruction()
    {
        $path = 'test.php';

        $this->assertFalse($this->object->hasSourceFile($path));
        $this->assertNull($this->object->getSourceFile($path));
    }

    // hasSourceFiles()
    // getSourceFiles()

    /**
     * @test
     */
    public function shouldCountZeroSourceFilesOnConstruction()
    {
        $this->assertFalse($this->object->hasSourceFiles());
        $this->assertEmpty($this->object->getSourceFiles());
    }

    // getServiceName()

    /**
     * @test
     */
    public function shouldNotHaveServiceNameOnConstruction()
    {
        $this->assertNull($this->object->getServiceName());
    }

    // getRepoToken()

    /**
     * @test
     */
    public function shouldNotHaveRepoTokenOnConstruction()
    {
        $this->assertNull($this->object->getRepoToken());
    }

    // getServiceJobId()

    /**
     * @test
     */
    public function shouldNotHaveServiceJobIdOnConstruction()
    {
        $this->assertNull($this->object->getServiceJobId());
    }

    // getServiceNumber()

    /**
     * @test
     */
    public function shouldNotHaveServiceNumberOnConstruction()
    {
        $this->assertNull($this->object->getServiceNumber());
    }

    // getServiceEventType()

    /**
     * @test
     */
    public function shouldNotHaveServiceEventTypeOnConstruction()
    {
        $this->assertNull($this->object->getServiceEventType());
    }

    // getServiceBuildUrl()

    /**
     * @test
     */
    public function shouldNotHaveServiceBuildUrlOnConstruction()
    {
        $this->assertNull($this->object->getServiceBuildUrl());
    }

    // getServiceBranch()

    /**
     * @test
     */
    public function shouldNotHaveServiceBranchOnConstruction()
    {
        $this->assertNull($this->object->getServiceBranch());
    }

    // getServicePullRequest()

    /**
     * @test
     */
    public function shouldNotHaveServicePullRequestOnConstruction()
    {
        $this->assertNull($this->object->getServicePullRequest());
    }

    // getGit()

    /**
     * @test
     */
    public function shouldNotHaveGitOnConstruction()
    {
        $this->assertNull($this->object->getGit());
    }

    // getRunAt()

    /**
     * @test
     */
    public function shouldNotHaveRunAtOnConstruction()
    {
        $this->assertNull($this->object->getRunAt());
    }

    // getMetrics()

    /**
     * @test
     */
    public function shouldHaveEmptyMetrics()
    {
        $metrics = $this->object->getMetrics();

        $this->assertSame(0, $metrics->getStatements());
        $this->assertSame(0, $metrics->getCoveredStatements());
        $this->assertSame(0, $metrics->getLineCoverage());
    }

    // setServiceName()

    /**
     * @test
     */
    public function shouldSetServiceName()
    {
        $expected = 'travis-ci';

        $obj = $this->object->setServiceName($expected);

        $this->assertSame($expected, $this->object->getServiceName());
        $this->assertSame($obj, $this->object);

        return $this->object;
    }

    // setRepoToken()

    /**
     * @test
     */
    public function shouldSetRepoToken()
    {
        $expected = 'token';

        $obj = $this->object->setRepoToken($expected);

        $this->assertSame($expected, $this->object->getRepoToken());
        $this->assertSame($obj, $this->object);

        return $this->object;
    }

    // setServiceJobId()

    /**
     * @test
     */
    public function shouldSetServiceJobId()
    {
        $expected = 'job_id';

        $obj = $this->object->setServiceJobId($expected);

        $this->assertSame($expected, $this->object->getServiceJobId());
        $this->assertSame($obj, $this->object);

        return $this->object;
    }

    // setGit()

    /**
     * @test
     */
    public function shouldSetGit()
    {
        $remotes = [new Remote()];
        $head = new Commit();
        $git = new Git('master', $head, $remotes);

        $obj = $this->object->setGit($git);

        $this->assertSame($git, $this->object->getGit());
        $this->assertSame($obj, $this->object);

        return $this->object;
    }

    // setRunAt()

    /**
     * @test
     */
    public function shouldSetRunAt()
    {
        $expected = '2013-04-04 11:22:33 +0900';

        $obj = $this->object->setRunAt($expected);

        $this->assertSame($expected, $this->object->getRunAt());
        $this->assertSame($obj, $this->object);

        return $this->object;
    }

    // addSourceFile()
    // sortSourceFiles()

    /**
     * @test
     */
    public function shouldAddSourceFile()
    {
        $sourceFile = $this->createSourceFile();

        $this->object->addSourceFile($sourceFile);
        $this->object->sortSourceFiles();

        $path = $sourceFile->getPath();

        $this->assertTrue($this->object->hasSourceFiles());
        $this->assertSame([$path => $sourceFile], $this->object->getSourceFiles());
        $this->assertTrue($this->object->hasSourceFile($path));
        $this->assertSame($sourceFile, $this->object->getSourceFile($path));
    }

    // toArray()

    /**
     * @test
     */
    public function shouldConvertToArray()
    {
        $expected = [
            'source_files' => [],
            'environment' => ['packagist_version' => Version::VERSION],
        ];

        $this->assertSame($expected, $this->object->toArray());
        $this->assertSame(json_encode($expected), (string) $this->object);
    }

    /**
     * @test
     */
    public function shouldConvertToArrayWithSourceFiles()
    {
        $sourceFile = $this->createSourceFile();

        $this->object->addSourceFile($sourceFile);

        $expected = [
            'source_files' => [$sourceFile->toArray()],
            'environment' => ['packagist_version' => Version::VERSION],
        ];

        $this->assertSame($expected, $this->object->toArray());
        $this->assertSame(json_encode($expected), (string) $this->object);
    }

    // service_name

    /**
     * @test
     * @depends shouldSetServiceName
     *
     * @param mixed $object
     */
    public function shouldConvertToArrayWithServiceName($object)
    {
        $item = 'travis-ci';

        $expected = [
            'service_name' => $item,
            'source_files' => [],
            'environment' => ['packagist_version' => Version::VERSION],
        ];

        $this->assertSame($expected, $object->toArray());
        $this->assertSame(json_encode($expected), (string) $object);
    }

    // service_job_id

    /**
     * @test
     * @depends shouldSetServiceJobId
     *
     * @param mixed $object
     */
    public function shouldConvertToArrayWithServiceJobId($object)
    {
        $item = 'job_id';

        $expected = [
            'service_job_id' => $item,
            'source_files' => [],
            'environment' => ['packagist_version' => Version::VERSION],
        ];

        $this->assertSame($expected, $object->toArray());
        $this->assertSame(json_encode($expected), (string) $object);
    }

    // repo_token

    /**
     * @test
     * @depends shouldSetRepoToken
     *
     * @param mixed $object
     */
    public function shouldConvertToArrayWithRepoToken($object)
    {
        $item = 'token';

        $expected = [
            'repo_token' => $item,
            'source_files' => [],
            'environment' => ['packagist_version' => Version::VERSION],
        ];

        $this->assertSame($expected, $object->toArray());
        $this->assertSame(json_encode($expected), (string) $object);
    }

    // git

    /**
     * @test
     * @depends shouldSetGit
     *
     * @param mixed $object
     */
    public function shouldConvertToArrayWithGit($object)
    {
        $remotes = [new Remote()];
        $head = new Commit();
        $git = new Git('master', $head, $remotes);

        $expected = [
            'git' => $git->toArray(),
            'source_files' => [],
            'environment' => ['packagist_version' => Version::VERSION],
        ];

        $this->assertSame($expected, $object->toArray());
        $this->assertSame(json_encode($expected), (string) $object);
    }

    // run_at

    /**
     * @test
     * @depends shouldSetRunAt
     *
     * @param mixed $object
     */
    public function shouldConvertToArrayWithRunAt($object)
    {
        $item = '2013-04-04 11:22:33 +0900';

        $expected = [
            'run_at' => $item,
            'source_files' => [],
            'environment' => ['packagist_version' => Version::VERSION],
        ];

        $this->assertSame($expected, $object->toArray());
        $this->assertSame(json_encode($expected), (string) $object);
    }

    // fillJobs()

    /**
     * @test
     */
    public function shouldFillJobsForServiceJobId()
    {
        $serviceName = 'travis-ci';
        $serviceJobId = '1.1';

        $env = [];
        $env['CI_NAME'] = $serviceName;
        $env['CI_JOB_ID'] = $serviceJobId;

        $object = $this->collectJsonFile();

        $same = $object->fillJobs($env);

        $this->assertSame($same, $object);
        $this->assertSame($serviceName, $object->getServiceName());
        $this->assertSame($serviceJobId, $object->getServiceJobId());
    }

    /**
     * @test
     */
    public function shouldFillJobsForServiceNumber()
    {
        $repoToken = 'token';
        $serviceName = 'circleci';
        $serviceNumber = '123';

        $env = [];
        $env['COVERALLS_REPO_TOKEN'] = $repoToken;
        $env['CI_NAME'] = $serviceName;
        $env['CI_BUILD_NUMBER'] = $serviceNumber;

        $object = $this->collectJsonFile();

        $same = $object->fillJobs($env);

        $this->assertSame($same, $object);
        $this->assertSame($repoToken, $object->getRepoToken());
        $this->assertSame($serviceName, $object->getServiceName());
        $this->assertSame($serviceNumber, $object->getServiceNumber());
    }

    /**
     * @test
     */
    public function shouldFillJobsForStandardizedEnvVars()
    {
        /*
         * CI_NAME=codeship
         * CI_BUILD_NUMBER=108821
         * CI_BUILD_URL=https://www.codeship.io/projects/2777/builds/108821
         * CI_BRANCH=master
         * CI_PULL_REQUEST=false
         */

        $repoToken = 'token';
        $serviceName = 'codeship';
        $serviceNumber = '108821';
        $serviceBuildUrl = 'https://www.codeship.io/projects/2777/builds/108821';
        $serviceBranch = 'master';
        $servicePullRequest = 'false';

        $env = [];
        $env['COVERALLS_REPO_TOKEN'] = $repoToken;
        $env['CI_NAME'] = $serviceName;
        $env['CI_BUILD_NUMBER'] = $serviceNumber;
        $env['CI_BUILD_URL'] = $serviceBuildUrl;
        $env['CI_BRANCH'] = $serviceBranch;
        $env['CI_PULL_REQUEST'] = $servicePullRequest;

        $object = $this->collectJsonFile();

        $same = $object->fillJobs($env);

        $this->assertSame($same, $object);
        $this->assertSame($repoToken, $object->getRepoToken());
        $this->assertSame($serviceName, $object->getServiceName());
        $this->assertSame($serviceNumber, $object->getServiceNumber());
        $this->assertSame($serviceBuildUrl, $object->getServiceBuildUrl());
        $this->assertSame($serviceBranch, $object->getServiceBranch());
        $this->assertSame($servicePullRequest, $object->getServicePullRequest());
    }

    /**
     * @test
     */
    public function shouldFillJobsForServiceEventType()
    {
        $repoToken = 'token';
        $serviceName = 'php-coveralls';
        $serviceEventType = 'manual';

        $env = [];
        $env['COVERALLS_REPO_TOKEN'] = $repoToken;
        $env['COVERALLS_RUN_LOCALLY'] = '1';
        $env['COVERALLS_EVENT_TYPE'] = $serviceEventType;
        $env['CI_NAME'] = $serviceName;

        $object = $this->collectJsonFile();

        $same = $object->fillJobs($env);

        $this->assertSame($same, $object);
        $this->assertSame($repoToken, $object->getRepoToken());
        $this->assertSame($serviceName, $object->getServiceName());
        $this->assertNull($object->getServiceJobId());
        $this->assertSame($serviceEventType, $object->getServiceEventType());
    }

    /**
     * @test
     */
    public function shouldFillJobsForUnsupportedJob()
    {
        $repoToken = 'token';

        $env = [];
        $env['COVERALLS_REPO_TOKEN'] = $repoToken;

        $object = $this->collectJsonFile();

        $same = $object->fillJobs($env);

        $this->assertSame($same, $object);
        $this->assertSame($repoToken, $object->getRepoToken());
    }

    /**
     * @test
     * @expectedException \PhpCoveralls\Bundle\CoverallsBundle\Entity\Exception\RequirementsNotSatisfiedException
     */
    public function throwRuntimeExceptionOnFillingJobsIfInvalidEnv()
    {
        $env = [];

        $object = $this->collectJsonFile();

        $object->fillJobs($env);
    }

    /**
     * @test
     * @expectedException \RuntimeException
     */
    public function throwRuntimeExceptionOnFillingJobsWithoutSourceFiles()
    {
        $env = [];
        $env['TRAVIS'] = true;
        $env['TRAVIS_JOB_ID'] = '1.1';

        $object = $this->collectJsonFileWithoutSourceFiles();

        $object->fillJobs($env);
    }

    // reportLineCoverage()

    /**
     * @test
     */
    public function shouldReportLineCoverage()
    {
        $object = $this->collectJsonFile();

        $this->assertSame(50.0, $object->reportLineCoverage());

        $metrics = $object->getMetrics();

        $this->assertSame(2, $metrics->getStatements());
        $this->assertSame(1, $metrics->getCoveredStatements());
        $this->assertSame(50.0, $metrics->getLineCoverage());
    }

    // excludeNoStatementsFiles()

    /**
     * @test
     */
    public function shouldExcludeNoStatementsFiles()
    {
        $srcDir = $this->srcDir . DIRECTORY_SEPARATOR;

        $object = $this->collectJsonFile();

        // before excluding
        $sourceFiles = $object->getSourceFiles();
        $this->assertCount(4, $sourceFiles);

        // filenames
        $paths = array_keys($sourceFiles);
        $filenames = array_map(function ($path) use ($srcDir) {return str_replace($srcDir, '', $path); }, $paths);

        $this->assertContains('test.php', $filenames);
        $this->assertContains('test2.php', $filenames);
        $this->assertContains('TestInterface.php', $filenames);
        $this->assertContains('AbstractClass.php', $filenames);

        // after excluding
        $object->excludeNoStatementsFiles();

        $sourceFiles = $object->getSourceFiles();
        $this->assertCount(2, $sourceFiles);

        // filenames
        $paths = array_keys($sourceFiles);
        $filenames = array_map(function ($path) use ($srcDir) {return str_replace($srcDir, '', $path); }, $paths);

        $this->assertContains('test.php', $filenames);
        $this->assertContains('test2.php', $filenames);
        $this->assertNotContains('TestInterface.php', $filenames);
        $this->assertNotContains('AbstractClass.php', $filenames);
    }

    /**
     * @return SourceFile
     */
    protected function createSourceFile()
    {
        $filename = 'test.php';
        $path = $this->srcDir . DIRECTORY_SEPARATOR . $filename;

        return new SourceFile($path, $filename);
    }

    /**
     * @return string
     */
    protected function getCloverXml()
    {
        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<coverage generated="1365848893">
  <project timestamp="1365848893">
    <file name="%s/test.php">
      <class name="TestFile" namespace="global">
        <metrics methods="1" coveredmethods="0" conditionals="0" coveredconditionals="0" statements="1" coveredstatements="0" elements="2" coveredelements="0"/>
      </class>
      <line num="5" type="method" name="__construct" crap="1" count="0"/>
      <line num="7" type="stmt" count="1"/>
    </file>
    <file name="%s/TestInterface.php">
      <class name="TestInterface" namespace="global">
        <metrics methods="1" coveredmethods="0" conditionals="0" coveredconditionals="0" statements="0" coveredstatements="0" elements="1" coveredelements="0"/>
      </class>
      <line num="5" type="method" name="hello" crap="1" count="0"/>
    </file>
    <file name="%s/AbstractClass.php">
      <class name="AbstractClass" namespace="global">
        <metrics methods="1" coveredmethods="0" conditionals="0" coveredconditionals="0" statements="0" coveredstatements="0" elements="1" coveredelements="0"/>
      </class>
      <line num="5" type="method" name="hello" crap="1" count="0"/>
    </file>
    <file name="dummy.php">
      <class name="TestFile" namespace="global">
        <metrics methods="1" coveredmethods="0" conditionals="0" coveredconditionals="0" statements="1" coveredstatements="0" elements="2" coveredelements="0"/>
      </class>
      <line num="5" type="method" name="__construct" crap="1" count="0"/>
      <line num="7" type="stmt" count="0"/>
    </file>
    <package name="Hoge">
      <file name="%s/test2.php">
        <class name="TestFile" namespace="Hoge">
          <metrics methods="1" coveredmethods="0" conditionals="0" coveredconditionals="0" statements="1" coveredstatements="0" elements="2" coveredelements="0"/>
        </class>
        <line num="6" type="method" name="__construct" crap="1" count="0"/>
        <line num="8" type="stmt" count="0"/>
      </file>
    </package>
  </project>
</coverage>
XML;

        return sprintf($xml, $this->srcDir, $this->srcDir, $this->srcDir, $this->srcDir);
    }

    /**
     * @return \SimpleXMLElement
     */
    protected function createCloverXml()
    {
        $xml = $this->getCloverXml();

        return simplexml_load_string($xml);
    }

    /**
     * @return JsonFile
     */
    protected function collectJsonFile()
    {
        $xml = $this->createCloverXml();
        $collector = new CloverXmlCoverageCollector();

        return $collector->collect($xml, $this->srcDir);
    }

    /**
     * @return string
     */
    protected function getNoSourceCloverXml()
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<coverage generated="1365848893">
  <project timestamp="1365848893">
    <file name="dummy.php">
      <class name="TestFile" namespace="global">
        <metrics methods="1" coveredmethods="0" conditionals="0" coveredconditionals="0" statements="1" coveredstatements="0" elements="2" coveredelements="0"/>
      </class>
      <line num="5" type="method" name="__construct" crap="1" count="0"/>
      <line num="7" type="stmt" count="0"/>
    </file>
  </project>
</coverage>
XML;
    }

    /**
     * @return \SimpleXMLElement
     */
    protected function createNoSourceCloverXml()
    {
        $xml = $this->getNoSourceCloverXml();

        return simplexml_load_string($xml);
    }

    /**
     * @return JsonFile
     */
    protected function collectJsonFileWithoutSourceFiles()
    {
        $xml = $this->createNoSourceCloverXml();
        $collector = new CloverXmlCoverageCollector();

        return $collector->collect($xml, $this->srcDir);
    }
}
