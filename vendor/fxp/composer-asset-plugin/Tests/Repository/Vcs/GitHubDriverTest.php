<?php

/*
 * This file is part of the Fxp Composer Asset Plugin package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Composer\AssetPlugin\Tests\Repository\Vcs;

use Composer\Cache;
use Composer\Downloader\TransportException;
use Composer\IO\IOInterface;
use Composer\Util\Filesystem;
use Composer\Config;
use Composer\Config\ConfigSourceInterface;
use Composer\Util\ProcessExecutor;
use Composer\Util\RemoteFilesystem;
use Fxp\Composer\AssetPlugin\Repository\Vcs\GitHubDriver;

/**
 * Tests of vcs github repository.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class GitHubDriverTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Config
     */
    private $config;

    public function setUp()
    {
        $this->config = new Config();
        $this->config->merge(array(
            'config' => array(
                'home' => sys_get_temp_dir().'/composer-test',
                'cache-repo-dir' => sys_get_temp_dir().'/composer-test-cache',
            ),
        ));
    }

    public function tearDown()
    {
        $fs = new Filesystem();
        $fs->removeDirectory(sys_get_temp_dir().'/composer-test');
        $fs->removeDirectory(sys_get_temp_dir().'/composer-test-cache');
    }

    public function getAssetTypes()
    {
        return array(
            array('npm', 'package.json'),
            array('bower', 'bower.json'),
        );
    }

    /**
     * @dataProvider getAssetTypes
     */
    public function testPrivateRepository($type, $filename)
    {
        $repoUrl = 'http://github.com/composer-test/repo-name';
        $repoApiUrl = 'https://api.github.com/repos/composer-test/repo-name';
        $repoSshUrl = 'git@github.com:composer-test/repo-name.git';
        $identifier = 'v0.0.0';
        $sha = 'SOMESHA';

        $io = $this->getMock('Composer\IO\IOInterface');
        $io->expects($this->any())
            ->method('isInteractive')
            ->will($this->returnValue(true));

        $remoteFilesystem = $this->getMockBuilder('Composer\Util\RemoteFilesystem')
            ->setConstructorArgs(array($io))
            ->getMock();

        $process = $this->getMock('Composer\Util\ProcessExecutor');
        $process->expects($this->any())
            ->method('execute')
            ->will($this->returnValue(1));

        $remoteFilesystem->expects($this->at(0))
            ->method('getContents')
            ->with($this->equalTo('github.com'), $this->equalTo($repoApiUrl), $this->equalTo(false))
            ->will($this->throwException(new TransportException('HTTP/1.1 404 Not Found', 404)));

        $io->expects($this->once())
            ->method('askAndHideAnswer')
            ->with($this->equalTo('Token (hidden): '))
            ->will($this->returnValue('sometoken'));

        $io->expects($this->any())
            ->method('setAuthentication')
            ->with($this->equalTo('github.com'), $this->matchesRegularExpression('{sometoken|abcdef}'), $this->matchesRegularExpression('{x-oauth-basic}'));

        $remoteFilesystem->expects($this->at(1))
            ->method('getContents')
            ->with($this->equalTo('github.com'), $this->equalTo('https://github.com/composer-test/repo-name'), $this->equalTo(false))
            ->will($this->returnValue(''));

        $remoteFilesystem->expects($this->at(2))
            ->method('getContents')
            ->will($this->returnValue(''));

        $remoteFilesystem->expects($this->at(3))
            ->method('getContents')
            ->with($this->equalTo('github.com'), $this->equalTo($repoApiUrl), $this->equalTo(false))
            ->will($this->throwException(new TransportException('HTTP/1.1 404 Not Found', 404)));

        $remoteFilesystem->expects($this->at(4))
            ->method('getContents')
            ->with($this->equalTo('github.com'), $this->equalTo('https://api.github.com/rate_limit'), $this->equalTo(false))
            ->will($this->returnValue('{}'));

        $remoteFilesystem->expects($this->at(5))
            ->method('getContents')
            ->with($this->equalTo('github.com'), $this->equalTo($repoApiUrl), $this->equalTo(false))
            ->will($this->returnValue($this->createJsonComposer(array('master_branch' => 'test_master', 'private' => true))));

        $configSource = $this->getMock('Composer\Config\ConfigSourceInterface');
        $authConfigSource = $this->getMock('Composer\Config\ConfigSourceInterface');

        /* @var ConfigSourceInterface $configSource */
        /* @var ConfigSourceInterface $authConfigSource */
        /* @var ProcessExecutor $process */
        /* @var RemoteFilesystem $remoteFilesystem */
        /* @var IOInterface $io */

        $this->config->setConfigSource($configSource);
        $this->config->setAuthConfigSource($authConfigSource);

        $repoConfig = array(
            'url' => $repoUrl,
            'asset-type' => $type,
            'filename' => $filename,
        );

        $gitHubDriver = new GitHubDriver($repoConfig, $io, $this->config, $process, $remoteFilesystem);
        $gitHubDriver->initialize();
        $this->setAttribute($gitHubDriver, 'tags', array($identifier => $sha));

        $this->assertEquals('test_master', $gitHubDriver->getRootIdentifier());

        $dist = $gitHubDriver->getDist($sha);
        $this->assertEquals('zip', $dist['type']);
        $this->assertEquals('https://api.github.com/repos/composer-test/repo-name/zipball/SOMESHA', $dist['url']);
        $this->assertEquals('SOMESHA', $dist['reference']);

        $source = $gitHubDriver->getSource($sha);
        $this->assertEquals('git', $source['type']);
        $this->assertEquals($repoSshUrl, $source['url']);
        $this->assertEquals('SOMESHA', $source['reference']);
    }

    /**
     * @dataProvider getAssetTypes
     */
    public function testPublicRepository($type, $filename)
    {
        $repoUrl = 'http://github.com/composer-test/repo-name';
        $repoApiUrl = 'https://api.github.com/repos/composer-test/repo-name';
        $identifier = 'v0.0.0';
        $sha = 'SOMESHA';

        $io = $this->getMock('Composer\IO\IOInterface');
        $io->expects($this->any())
            ->method('isInteractive')
            ->will($this->returnValue(true));

        $remoteFilesystem = $this->getMockBuilder('Composer\Util\RemoteFilesystem')
            ->setConstructorArgs(array($io))
            ->getMock();

        $remoteFilesystem->expects($this->at(0))
            ->method('getContents')
            ->with($this->equalTo('github.com'), $this->equalTo($repoApiUrl), $this->equalTo(false))
            ->will($this->returnValue($this->createJsonComposer(array('master_branch' => 'test_master'))));

        $repoConfig = array(
            'url' => $repoUrl,
            'asset-type' => $type,
            'filename' => $filename,
        );
        $repoUrl = 'https://github.com/composer-test/repo-name.git';

        /* @var IOInterface $io */
        /* @var RemoteFilesystem $remoteFilesystem */

        $gitHubDriver = new GitHubDriver($repoConfig, $io, $this->config, null, $remoteFilesystem);
        $gitHubDriver->initialize();
        $this->setAttribute($gitHubDriver, 'tags', array($identifier => $sha));

        $this->assertEquals('test_master', $gitHubDriver->getRootIdentifier());

        $dist = $gitHubDriver->getDist($sha);
        $this->assertEquals('zip', $dist['type']);
        $this->assertEquals('https://api.github.com/repos/composer-test/repo-name/zipball/SOMESHA', $dist['url']);
        $this->assertEquals($sha, $dist['reference']);

        $source = $gitHubDriver->getSource($sha);
        $this->assertEquals('git', $source['type']);
        $this->assertEquals($repoUrl, $source['url']);
        $this->assertEquals($sha, $source['reference']);
    }

    /**
     * @dataProvider getAssetTypes
     */
    public function testPublicRepository2($type, $filename)
    {
        $repoUrl = 'http://github.com/composer-test/repo-name';
        $repoApiUrl = 'https://api.github.com/repos/composer-test/repo-name';
        $identifier = 'feature/3.2-foo';
        $sha = 'SOMESHA';

        $io = $this->getMock('Composer\IO\IOInterface');
        $io->expects($this->any())
            ->method('isInteractive')
            ->will($this->returnValue(true));

        $remoteFilesystem = $this->getMockBuilder('Composer\Util\RemoteFilesystem')
            ->setConstructorArgs(array($io))
            ->getMock();

        $remoteFilesystem->expects($this->at(0))
            ->method('getContents')
            ->with($this->equalTo('github.com'), $this->equalTo($repoApiUrl), $this->equalTo(false))
            ->will($this->returnValue($this->createJsonComposer(array('master_branch' => 'test_master'))));

        $remoteFilesystem->expects($this->at(1))
            ->method('getContents')
            ->with($this->equalTo('github.com'), $this->equalTo('https://api.github.com/repos/composer-test/repo-name/contents/'.$filename.'?ref=feature%2F3.2-foo'), $this->equalTo(false))
            ->will($this->returnValue('{"encoding":"base64","content":"'.base64_encode('{"support": {"source": "'.$repoUrl.'" }}').'"}'));

        $remoteFilesystem->expects($this->at(2))
            ->method('getContents')
            ->with($this->equalTo('github.com'), $this->equalTo('https://api.github.com/repos/composer-test/repo-name/commits/feature%2F3.2-foo'), $this->equalTo(false))
            ->will($this->returnValue('{"commit": {"committer":{ "date": "2012-09-10"}}}'));

        $repoConfig = array(
            'url' => $repoUrl,
            'asset-type' => $type,
            'filename' => $filename,
        );
        $repoUrl = 'https://github.com/composer-test/repo-name.git';

        /* @var IOInterface $io */
        /* @var RemoteFilesystem $remoteFilesystem */

        $gitHubDriver = new GitHubDriver($repoConfig, $io, $this->config, null, $remoteFilesystem);
        $gitHubDriver->initialize();
        $this->setAttribute($gitHubDriver, 'tags', array($identifier => $sha));

        $this->assertEquals('test_master', $gitHubDriver->getRootIdentifier());

        $dist = $gitHubDriver->getDist($sha);
        $this->assertEquals('zip', $dist['type']);
        $this->assertEquals('https://api.github.com/repos/composer-test/repo-name/zipball/SOMESHA', $dist['url']);
        $this->assertEquals($sha, $dist['reference']);

        $source = $gitHubDriver->getSource($sha);
        $this->assertEquals('git', $source['type']);
        $this->assertEquals($repoUrl, $source['url']);
        $this->assertEquals($sha, $source['reference']);

        $gitHubDriver->getComposerInformation($identifier);
    }

    /**
     * @dataProvider getAssetTypes
     */
    public function testPrivateRepositoryNoInteraction($type, $filename)
    {
        $repoUrl = 'http://github.com/composer-test/repo-name';
        $repoApiUrl = 'https://api.github.com/repos/composer-test/repo-name';
        $repoSshUrl = 'git@github.com:composer-test/repo-name.git';
        $identifier = 'v0.0.0';
        $sha = 'SOMESHA';

        $process = $this->getMockBuilder('Composer\Util\ProcessExecutor')
            ->disableOriginalConstructor()
            ->getMock();

        $io = $this->getMock('Composer\IO\IOInterface');
        $io->expects($this->any())
            ->method('isInteractive')
            ->will($this->returnValue(false));

        $remoteFilesystem = $this->getMockBuilder('Composer\Util\RemoteFilesystem')
            ->setConstructorArgs(array($io))
            ->getMock();

        $remoteFilesystem->expects($this->at(0))
            ->method('getContents')
            ->with($this->equalTo('github.com'), $this->equalTo($repoApiUrl), $this->equalTo(false))
            ->will($this->throwException(new TransportException('HTTP/1.1 404 Not Found', 404)));

        $remoteFilesystem->expects($this->at(1))
            ->method('getContents')
            ->with($this->equalTo('github.com'), $this->equalTo('https://github.com/composer-test/repo-name'), $this->equalTo(false))
            ->will($this->returnValue(''));

        $remoteFilesystem->expects($this->at(2))
            ->method('getContents')
            ->will($this->returnValue(''));

        $remoteFilesystem->expects($this->at(3))
            ->method('getContents')
            ->with($this->equalTo('github.com'), $this->equalTo($repoApiUrl), $this->equalTo(false))
            ->will($this->throwException(new TransportException('HTTP/1.1 404 Not Found', 404)));

        // clean local clone if present
        $fs = new Filesystem();
        $fs->removeDirectory(sys_get_temp_dir().'/composer-test');

        $process->expects($this->at(0))
            ->method('execute')
            ->with($this->equalTo('git config github.accesstoken'))
            ->will($this->returnValue(1));

        $process->expects($this->at(1))
            ->method('execute')
            ->with($this->stringContains($repoSshUrl))
            ->will($this->returnValue(0));

        $process->expects($this->at(2))
            ->method('execute')
            ->with($this->stringContains('git show-ref --tags'));

        $process->expects($this->at(3))
            ->method('splitLines')
            ->will($this->returnValue(array($sha.' refs/tags/'.$identifier)));

        $process->expects($this->at(4))
            ->method('execute')
            ->with($this->stringContains('git branch --no-color --no-abbrev -v'));

        $process->expects($this->at(5))
            ->method('splitLines')
            ->will($this->returnValue(array('  test_master     edf93f1fccaebd8764383dc12016d0a1a9672d89 Fix test & behavior')));

        $process->expects($this->at(6))
            ->method('execute')
            ->with($this->stringContains('git branch --no-color'));

        $process->expects($this->at(7))
            ->method('splitLines')
            ->will($this->returnValue(array('* test_master')));

        $repoConfig = array(
            'url' => $repoUrl,
            'asset-type' => $type,
            'filename' => $filename,
        );

        /* @var IOInterface $io */
        /* @var RemoteFilesystem $remoteFilesystem */
        /* @var ProcessExecutor $process */

        $gitHubDriver = new GitHubDriver($repoConfig, $io, $this->config, $process, $remoteFilesystem);
        $gitHubDriver->initialize();

        $this->assertEquals('test_master', $gitHubDriver->getRootIdentifier());

        $dist = $gitHubDriver->getDist($sha);
        $this->assertEquals('zip', $dist['type']);
        $this->assertEquals('https://api.github.com/repos/composer-test/repo-name/zipball/SOMESHA', $dist['url']);
        $this->assertEquals($sha, $dist['reference']);

        $source = $gitHubDriver->getSource($identifier);
        $this->assertEquals('git', $source['type']);
        $this->assertEquals($repoSshUrl, $source['url']);
        $this->assertEquals($identifier, $source['reference']);

        $source = $gitHubDriver->getSource($sha);
        $this->assertEquals('git', $source['type']);
        $this->assertEquals($repoSshUrl, $source['url']);
        $this->assertEquals($sha, $source['reference']);
    }

    /**
     * @dataProvider getAssetTypes
     */
    public function testGetComposerInformationWithGitDriver($type, $filename)
    {
        $repoUrl = 'https://github.com/composer-test/repo-name';
        $identifier = 'v0.0.0';

        $io = $this->getMock('Composer\IO\IOInterface');
        $io->expects($this->any())
            ->method('isInteractive')
            ->will($this->returnValue(true));

        $repoConfig = array(
            'url' => $repoUrl,
            'asset-type' => $type,
            'filename' => $filename,
            'no-api' => true,
        );

        $process = $this->getMock('Composer\Util\ProcessExecutor');
        $process->expects($this->any())
            ->method('splitLines')
            ->will($this->returnValue(array()));
        $process->expects($this->any())
            ->method('execute')
            ->will($this->returnCallback(function () {
                        return 0;
                    }));

        /* @var IOInterface $io */
        /* @var ProcessExecutor $process */

        $gitHubDriver = new GitHubDriver($repoConfig, $io, $this->config, $process, null);
        $gitHubDriver->initialize();

        $validEmpty = array(
            '_nonexistent_package' => true,
        );

        $this->assertSame($validEmpty, $gitHubDriver->getComposerInformation($identifier));
    }

    /**
     * @dataProvider getAssetTypes
     */
    public function testGetComposerInformationWithCodeCache($type, $filename)
    {
        $repoUrl = 'http://github.com/composer-test/repo-name';
        $repoApiUrl = 'https://api.github.com/repos/composer-test/repo-name';
        $identifier = 'dev-master';
        $sha = '92bebbfdcde75ef2368317830e54b605bc938123';

        $io = $this->getMock('Composer\IO\IOInterface');
        $io->expects($this->any())
            ->method('isInteractive')
            ->will($this->returnValue(true));

        /* @var IOInterface $io */
        /* @var RemoteFilesystem $remoteFilesystem */
        $remoteFilesystem = $this->createMockRremoteFilesystem($io, $repoApiUrl, $filename, $sha, false);
        $repoConfig = array(
            'url' => $repoUrl,
            'asset-type' => $type,
            'filename' => $filename,
        );

        $gitHubDriver = new GitHubDriver($repoConfig, $io, $this->config, null, $remoteFilesystem);
        $gitHubDriver->initialize();
        $this->setAttribute($gitHubDriver, 'tags', array($identifier => $sha));
        $this->setAttribute($gitHubDriver, 'hasIssues', true);

        $composer1 = $gitHubDriver->getComposerInformation($sha);
        $composer2 = $gitHubDriver->getComposerInformation($sha);

        $this->assertSame($composer1, $composer2);
    }

    /**
     * @dataProvider getAssetTypes
     */
    public function testGetComposerInformationWithFilesystemCache($type, $filename)
    {
        $repoUrl = 'http://github.com/composer-test/repo-name';
        $repoApiUrl = 'https://api.github.com/repos/composer-test/repo-name';
        $identifier = 'dev-master';
        $sha = '92bebbfdcde75ef2368317830e54b605bc938123';

        $io = $this->getMock('Composer\IO\IOInterface');
        $io->expects($this->any())
            ->method('isInteractive')
            ->will($this->returnValue(true));

        /* @var IOInterface $io */
        /* @var RemoteFilesystem $remoteFilesystem1 */
        $remoteFilesystem1 = $this->createMockRremoteFilesystem($io, $repoApiUrl, $filename, $sha, false);
        /* @var RemoteFilesystem $remoteFilesystem2 */
        $remoteFilesystem2 = $this->createMockRremoteFilesystem($io, $repoApiUrl, $filename, $sha, true);
        $repoConfig = array(
            'url' => $repoUrl,
            'asset-type' => $type,
            'filename' => $filename,
        );

        $gitHubDriver1 = new GitHubDriver($repoConfig, $io, $this->config, null, $remoteFilesystem1);
        $gitHubDriver2 = new GitHubDriver($repoConfig, $io, $this->config, null, $remoteFilesystem2);
        $gitHubDriver1->initialize();
        $gitHubDriver2->initialize();
        $this->setAttribute($gitHubDriver1, 'tags', array($identifier => $sha));
        $this->setAttribute($gitHubDriver1, 'hasIssues', true);
        $this->setAttribute($gitHubDriver2, 'tags', array($identifier => $sha));
        $this->setAttribute($gitHubDriver2, 'hasIssues', true);

        $composer1 = $gitHubDriver1->getComposerInformation($sha);
        $composer2 = $gitHubDriver2->getComposerInformation($sha);

        $this->assertSame($composer1, $composer2);
    }

    /**
     * @dataProvider getAssetTypes
     */
    public function testGetComposerInformationWithEmptyContent($type, $filename)
    {
        $repoUrl = 'http://github.com/composer-test/repo-name';
        $repoApiUrl = 'https://api.github.com/repos/composer-test/repo-name';
        $identifier = 'v0.0.0';

        $io = $this->getMock('Composer\IO\IOInterface');

        $remoteFilesystem = $this->getMockBuilder('Composer\Util\RemoteFilesystem')
            ->setConstructorArgs(array($io))
            ->getMock();

        $remoteFilesystem->expects($this->at(0))
            ->method('getContents')
            ->with($this->equalTo('github.com'), $this->equalTo($repoApiUrl), $this->equalTo(false))
            ->will($this->returnValue($this->createJsonComposer(array('master_branch' => 'test_master'))));

        $remoteFilesystem->expects($this->at(1))
            ->method('getContents')
            ->with($this->equalTo('github.com'), $this->equalTo('https://api.github.com/repos/composer-test/repo-name/contents/'.$filename.'?ref='.$identifier), $this->equalTo(false))
            ->will($this->throwException(new TransportException('Not Found', 404)));
        $remoteFilesystem->expects($this->at(2))
            ->method('getContents')
            ->with($this->equalTo('github.com'), $this->equalTo('https://api.github.com/repos/composer-test/repo-name/contents/'.$filename.'?ref='.$identifier), $this->equalTo(false))
            ->will($this->throwException(new TransportException('Not Found', 404)));

        $repoConfig = array(
            'url' => $repoUrl,
            'asset-type' => $type,
            'filename' => $filename,
        );

        /* @var IOInterface $io */
        /* @var RemoteFilesystem $remoteFilesystem */

        $gitHubDriver = new GitHubDriver($repoConfig, $io, $this->config, null, $remoteFilesystem);
        $gitHubDriver->initialize();

        $validEmpty = array(
            '_nonexistent_package' => true,
        );

        $this->assertSame($validEmpty, $gitHubDriver->getComposerInformation($identifier));
    }

    /**
     * @dataProvider getAssetTypes
     */
    public function testGetComposerInformationWithRuntimeException($type, $filename)
    {
        $this->setExpectedException('RuntimeException');

        $repoUrl = 'http://github.com/composer-test/repo-name';
        $repoApiUrl = 'https://api.github.com/repos/composer-test/repo-name';
        $identifier = 'v0.0.0';

        $io = $this->getMock('Composer\IO\IOInterface');

        $remoteFilesystem = $this->getMockBuilder('Composer\Util\RemoteFilesystem')
            ->setConstructorArgs(array($io))
            ->getMock();

        $remoteFilesystem->expects($this->at(0))
            ->method('getContents')
            ->with($this->equalTo('github.com'), $this->equalTo($repoApiUrl), $this->equalTo(false))
            ->will($this->returnValue($this->createJsonComposer(array('master_branch' => 'test_master'))));

        $remoteFilesystem->expects($this->at(1))
            ->method('getContents')
            ->with($this->equalTo('github.com'), $this->equalTo('https://api.github.com/repos/composer-test/repo-name/contents/'.$filename.'?ref='.$identifier), $this->equalTo(false))
            ->will($this->returnValue('{"encoding":"base64","content":""}'));

        $repoConfig = array(
            'url' => $repoUrl,
            'asset-type' => $type,
            'filename' => $filename,
        );

        /* @var IOInterface $io */
        /* @var RemoteFilesystem $remoteFilesystem */

        $gitHubDriver = new GitHubDriver($repoConfig, $io, $this->config, null, $remoteFilesystem);
        $gitHubDriver->initialize();

        $gitHubDriver->getComposerInformation($identifier);
    }

    /**
     * @dataProvider getAssetTypes
     */
    public function testGetComposerInformationWithTransportException($type, $filename)
    {
        $this->setExpectedException('RuntimeException');

        $repoUrl = 'http://github.com/composer-test/repo-name';
        $repoApiUrl = 'https://api.github.com/repos/composer-test/repo-name';
        $identifier = 'v0.0.0';

        $io = $this->getMock('Composer\IO\IOInterface');

        $remoteFilesystem = $this->getMockBuilder('Composer\Util\RemoteFilesystem')
            ->setConstructorArgs(array($io))
            ->getMock();

        $remoteFilesystem->expects($this->at(0))
            ->method('getContents')
            ->with($this->equalTo('github.com'), $this->equalTo($repoApiUrl), $this->equalTo(false))
            ->will($this->returnValue($this->createJsonComposer(array('master_branch' => 'test_master'))));

        $remoteFilesystem->expects($this->at(1))
            ->method('getContents')
            ->with($this->equalTo('github.com'), $this->equalTo('https://api.github.com/repos/composer-test/repo-name/contents/'.$filename.'?ref='.$identifier), $this->equalTo(false))
            ->will($this->throwException(new TransportException('Mock exception code 404', 404)));

        $remoteFilesystem->expects($this->at(2))
            ->method('getContents')
            ->with($this->equalTo('github.com'), $this->equalTo('https://api.github.com/repos/composer-test/repo-name/contents/'.$filename.'?ref='.$identifier), $this->equalTo(false))
            ->will($this->throwException(new TransportException('Mock exception code 400', 400)));

        $repoConfig = array(
            'url' => $repoUrl,
            'asset-type' => $type,
            'filename' => $filename,
        );

        /* @var IOInterface $io */
        /* @var RemoteFilesystem $remoteFilesystem */

        $gitHubDriver = new GitHubDriver($repoConfig, $io, $this->config, null, $remoteFilesystem);
        $gitHubDriver->initialize();

        $gitHubDriver->getComposerInformation($identifier);
    }

    /**
     * @dataProvider getAssetTypes
     */
    public function testRedirectUrlRepository($type, $filename)
    {
        $repoUrl = 'http://github.com/composer-test/repo-name';
        $repoApiUrl = 'https://api.github.com/repos/composer-test/repo-name';
        $identifier = 'v0.0.0';
        $sha = 'SOMESHA';

        $io = $this->getMock('Composer\IO\IOInterface');
        $io->expects($this->any())
            ->method('isInteractive')
            ->will($this->returnValue(true));

        $remoteFilesystem = $this->getMockBuilder('Composer\Util\RemoteFilesystem')
            ->setConstructorArgs(array($io))
            ->getMock();

        $remoteFilesystem->expects($this->at(0))
            ->method('getContents')
            ->with($this->equalTo('github.com'), $this->equalTo($repoApiUrl), $this->equalTo(false))
            ->will($this->throwException(new TransportException('HTTP/1.1 404 Not Found', 404)));

        $remoteFilesystem->expects($this->at(1))
            ->method('getContents')
            ->with($this->equalTo('github.com'), $this->equalTo('https://github.com/composer-test/repo-name'), $this->equalTo(false))
            ->will($this->returnValue(''));

        $remoteFilesystem->expects($this->at(2))
            ->method('getLastHeaders')
            ->will($this->returnValue(array(
                'HTTP/1.1 301 Moved Permanently',
                'Header-parameter: test',
                'Location: '.$repoUrl.'-new',
            )));

        $remoteFilesystem->expects($this->at(3))
            ->method('getContents')
            ->with($this->equalTo('github.com'), $this->equalTo($repoApiUrl.'-new'), $this->equalTo(false))
            ->will($this->returnValue($this->createJsonComposer(array('master_branch' => 'test_master'))));

        $repoConfig = array(
            'url' => $repoUrl,
            'asset-type' => $type,
            'filename' => $filename,
        );
        $repoUrl = 'https://github.com/composer-test/repo-name.git';

        /* @var IOInterface $io */
        /* @var RemoteFilesystem $remoteFilesystem */

        $gitHubDriver = new GitHubDriver($repoConfig, $io, $this->config, null, $remoteFilesystem);
        $gitHubDriver->initialize();
        $this->setAttribute($gitHubDriver, 'tags', array($identifier => $sha));

        $this->assertEquals('test_master', $gitHubDriver->getRootIdentifier());

        $dist = $gitHubDriver->getDist($sha);
        $this->assertEquals('zip', $dist['type']);
        $this->assertEquals('https://api.github.com/repos/composer-test/repo-name/zipball/SOMESHA', $dist['url']);
        $this->assertEquals($sha, $dist['reference']);

        $source = $gitHubDriver->getSource($sha);
        $this->assertEquals('git', $source['type']);
        $this->assertEquals($repoUrl, $source['url']);
        $this->assertEquals($sha, $source['reference']);
    }

    /**
     * @dataProvider getAssetTypes
     */
    public function testRedirectUrlWithNonexistentRepository($type, $filename)
    {
        $this->setExpectedException('RuntimeException');

        $repoUrl = 'http://github.com/composer-test/repo-name';
        $repoApiUrl = 'https://api.github.com/repos/composer-test/repo-name';
        $identifier = 'v0.0.0';

        $io = $this->getMock('Composer\IO\IOInterface');
        $io->expects($this->any())
            ->method('isInteractive')
            ->will($this->returnValue(true));

        $remoteFilesystem = $this->getMockBuilder('Composer\Util\RemoteFilesystem')
            ->setConstructorArgs(array($io))
            ->getMock();

        $remoteFilesystem->expects($this->at(0))
            ->method('getContents')
            ->with($this->equalTo('github.com'), $this->equalTo($repoApiUrl), $this->equalTo(false))
            ->will($this->throwException(new TransportException('HTTP/1.1 404 Not Found', 404)));

        $io->expects($this->once())
            ->method('askAndHideAnswer')
            ->with($this->equalTo('Token (hidden): '))
            ->will($this->returnValue('sometoken'));

        $io->expects($this->any())
            ->method('setAuthentication')
            ->with($this->equalTo('github.com'), $this->matchesRegularExpression('{sometoken|abcdef}'), $this->matchesRegularExpression('{x-oauth-basic}'));

        $remoteFilesystem->expects($this->at(1))
            ->method('getContents')
            ->with($this->equalTo('github.com'), $this->equalTo('https://github.com/composer-test/repo-name'), $this->equalTo(false))
            ->will($this->throwException(new TransportException('HTTP/1.1 404 Not Found', 404)));

        $remoteFilesystem->expects($this->at(2))
            ->method('getContents')
            ->with($this->equalTo('github.com'), $this->equalTo($repoApiUrl), $this->equalTo(false))
            ->will($this->throwException(new TransportException('HTTP/1.1 404 Not Found', 404)));

        $remoteFilesystem->expects($this->at(3))
            ->method('getContents')
            ->with($this->equalTo('github.com'), $this->equalTo('https://api.github.com/rate_limit'), $this->equalTo(false))
            ->will($this->returnValue('{}'));

        $remoteFilesystem->expects($this->at(4))
            ->method('getContents')
            ->with($this->equalTo('github.com'), $this->equalTo($repoApiUrl), $this->equalTo(false))
            ->will($this->throwException(new TransportException('HTTP/1.1 404 Not Found', 404)));

        $remoteFilesystem->expects($this->at(5))
            ->method('getContents')
            ->with($this->equalTo('github.com'), $this->equalTo($repoApiUrl.'/contents/'.$filename.'?ref='.$identifier), $this->equalTo(false))
            ->will($this->throwException(new TransportException('HTTP/1.1 404 Not Found', 404)));

        $configSource = $this->getMock('Composer\Config\ConfigSourceInterface');
        $authConfigSource = $this->getMock('Composer\Config\ConfigSourceInterface');

        /* @var ConfigSourceInterface $configSource */
        /* @var ConfigSourceInterface $authConfigSource */

        $this->config->setConfigSource($configSource);
        $this->config->setAuthConfigSource($authConfigSource);

        $repoConfig = array(
            'url' => $repoUrl,
            'asset-type' => $type,
            'filename' => $filename,
        );

        /* @var IOInterface $io */
        /* @var RemoteFilesystem $remoteFilesystem */

        $gitHubDriver = new GitHubDriver($repoConfig, $io, $this->config, null, $remoteFilesystem);
        $firstNonexistent = false;

        try {
            $gitHubDriver->initialize();
        } catch (TransportException $e) {
            $firstNonexistent = true;
        }

        $this->assertTrue($firstNonexistent);

        $gitHubDriver->getComposerInformation($identifier);
    }

    /**
     * @dataProvider getAssetTypes
     */
    public function testRedirectUrlRepositoryWithCache($type, $filename)
    {
        $originUrl = 'github.com';
        $owner = 'composer-test';
        $repository = 'repo-name';
        $repoUrl = 'http://'.$originUrl.'/'.$owner.'/'.$repository;
        $repoApiUrl = 'https://api.github.com/repos/composer-test/repo-name';
        $repoApiUrlNew = $repoApiUrl.'-new';
        $identifier = 'v0.0.0';
        $sha = 'SOMESHA';

        $io = $this->getMock('Composer\IO\IOInterface');
        $io->expects($this->any())
            ->method('isInteractive')
            ->will($this->returnValue(true));

        $remoteFilesystem = $this->getMockBuilder('Composer\Util\RemoteFilesystem')
            ->setConstructorArgs(array($io))
            ->getMock();

        $remoteFilesystem->expects($this->at(0))
            ->method('getContents')
            ->with($this->equalTo('github.com'), $this->equalTo($repoApiUrlNew), $this->equalTo(false))
            ->will($this->returnValue($this->createJsonComposer(array('master_branch' => 'test_master'))));

        $repoConfig = array(
            'url' => $repoUrl,
            'asset-type' => $type,
            'filename' => $filename,
        );
        $repoUrl = 'https://github.com/composer-test/repo-name.git';

        /* @var IOInterface $io */
        /* @var RemoteFilesystem $remoteFilesystem */

        $cache = new Cache($io, $this->config->get('cache-repo-dir').'/'.$originUrl.'/'.$owner.'/'.$repository);
        $cache->write('redirect-api', $repoApiUrlNew);

        $gitHubDriver = new GitHubDriver($repoConfig, $io, $this->config, null, $remoteFilesystem);
        $gitHubDriver->initialize();
        $this->setAttribute($gitHubDriver, 'tags', array($identifier => $sha));

        $this->assertEquals('test_master', $gitHubDriver->getRootIdentifier());

        $dist = $gitHubDriver->getDist($sha);
        $this->assertEquals('zip', $dist['type']);
        $this->assertEquals('https://api.github.com/repos/composer-test/repo-name/zipball/SOMESHA', $dist['url']);
        $this->assertEquals($sha, $dist['reference']);

        $source = $gitHubDriver->getSource($sha);
        $this->assertEquals('git', $source['type']);
        $this->assertEquals($repoUrl, $source['url']);
        $this->assertEquals($sha, $source['reference']);
    }

    public function getDataBranches()
    {
        $valid1 = array();
        $git1 = array();
        $valid2 = array(
            'master' => '0123456789abcdef0123456789abcdef01234567',
        );
        $git2 = array(
            'master 0123456789abcdef0123456789abcdef01234567 Comment',
        );
        $valid3 = array(
            'gh-pages' => '0123456789abcdef0123456789abcdef01234567',
        );
        $git3 = array(
            'gh-pages 0123456789abcdef0123456789abcdef01234567 Comment',
        );
        $valid4 = array(
            'master' => '0123456789abcdef0123456789abcdef01234567',
            'gh-pages' => '0123456789abcdef0123456789abcdef01234567',
        );
        $git4 = array(
            'master 0123456789abcdef0123456789abcdef01234567 Comment',
            'gh-pages 0123456789abcdef0123456789abcdef01234567 Comment',
        );

        return array(
            array('npm', 'package.json', $valid1, $git1),
            array('npm', 'package.json', $valid2, $git2),
            array('npm', 'package.json', $valid3, $git3),
            array('npm', 'package.json', $valid4, $git4),
            array('bower', 'bower.json', $valid1, $git1),
            array('bower', 'bower.json', $valid2, $git2),
            array('bower', 'bower.json', $valid3, $git3),
            array('bower', 'bower.json', $valid4, $git4),
        );
    }

    /**
     * @dataProvider getDataBranches
     */
    public function testGetBranchesWithGitDriver($type, $filename, array $branches, array $gitBranches)
    {
        $repoUrl = 'https://github.com/composer-test/repo-name';

        $io = $this->getMock('Composer\IO\IOInterface');
        $io->expects($this->any())
            ->method('isInteractive')
            ->will($this->returnValue(true));

        $repoConfig = array(
            'url' => $repoUrl,
            'asset-type' => $type,
            'filename' => $filename,
            'no-api' => true,
        );

        $process = $this->getMock('Composer\Util\ProcessExecutor');
        $process->expects($this->any())
            ->method('splitLines')
            ->will($this->returnValue($gitBranches));
        $process->expects($this->any())
            ->method('execute')
            ->will($this->returnCallback(function () {
                return 0;
            }));

        /* @var IOInterface $io */
        /* @var ProcessExecutor $process */

        $gitHubDriver = new GitHubDriver($repoConfig, $io, $this->config, $process, null);
        $gitHubDriver->initialize();

        $this->assertSame($branches, $gitHubDriver->getBranches());
    }

    /**
     * @dataProvider getDataBranches
     */
    public function testGetBranches($type, $filename, array $branches)
    {
        $repoUrl = 'http://github.com/composer-test/repo-name';
        $repoApiUrl = 'https://api.github.com/repos/composer-test/repo-name';
        $identifier = 'v0.0.0';
        $sha = 'SOMESHA';

        $io = $this->getMock('Composer\IO\IOInterface');
        $io->expects($this->any())
            ->method('isInteractive')
            ->will($this->returnValue(true));

        $remoteFilesystem = $this->getMockBuilder('Composer\Util\RemoteFilesystem')
            ->setConstructorArgs(array($io))
            ->getMock();

        $remoteFilesystem->expects($this->at(0))
            ->method('getContents')
            ->with($this->equalTo('github.com'), $this->equalTo($repoApiUrl), $this->equalTo(false))
            ->will($this->returnValue($this->createJsonComposer(array('master_branch' => 'gh-pages'))));

        $remoteFilesystem->expects($this->any())
            ->method('getLastHeaders')
            ->will($this->returnValue(array()));

        $githubBranches = array();
        foreach ($branches as $branch => $sha) {
            $githubBranches[] = array(
                'ref' => 'refs/heads/'.$branch,
                'object' => array(
                    'sha' => $sha,
                ),
            );
        }

        $remoteFilesystem->expects($this->at(1))
            ->method('getContents')
            ->will($this->returnValue(json_encode($githubBranches)));

        $repoConfig = array(
            'url' => $repoUrl,
            'asset-type' => $type,
            'filename' => $filename,
        );

        /* @var IOInterface $io */
        /* @var RemoteFilesystem $remoteFilesystem */

        $gitHubDriver = new GitHubDriver($repoConfig, $io, $this->config, null, $remoteFilesystem);
        $gitHubDriver->initialize();
        $this->setAttribute($gitHubDriver, 'tags', array($identifier => $sha));

        $this->assertEquals('gh-pages', $gitHubDriver->getRootIdentifier());
        $this->assertSame($branches, $gitHubDriver->getBranches());
    }

    /**
     * @param object $object
     * @param string $attribute
     * @param mixed  $value
     */
    protected function setAttribute($object, $attribute, $value)
    {
        $attr = new \ReflectionProperty($object, $attribute);
        $attr->setAccessible(true);
        $attr->setValue($object, $value);
    }

    /**
     * Creates the json composer content.
     *
     * @param array  $content The composer content
     * @param string $name    The name of repository
     * @param string $login   The username /organization of repository
     *
     * @return string The json content
     */
    protected function createJsonComposer(array $content, $name = 'repo-name', $login = 'composer-test')
    {
        return json_encode(array_merge_recursive($content, array(
            'name' => $name,
            'owner' => array(
                'login' => $login,
            ),
        )));
    }

    /**
     * @param IOInterface $io
     * @param string      $repoApiUrl
     * @param string      $filename
     * @param string      $sha
     * @param bool        $forCache
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function createMockRremoteFilesystem($io, $repoApiUrl, $filename, $sha, $forCache)
    {
        $remoteFilesystem = $this->getMockBuilder('Composer\Util\RemoteFilesystem')
            ->setConstructorArgs(array($io))
            ->getMock();

        $remoteFilesystem->expects($this->at(0))
            ->method('getContents')
            ->with($this->equalTo('github.com'), $this->equalTo($repoApiUrl), $this->equalTo(false))
            ->will($this->returnValue($this->createJsonComposer(array('master_branch' => 'test_master'))));

        if ($forCache) {
            return $remoteFilesystem;
        }

        $remoteFilesystem->expects($this->at(1))
            ->method('getContents')
            ->with($this->equalTo('github.com'), $this->equalTo('https://api.github.com/repos/composer-test/repo-name/contents/'.$filename.'?ref='.$sha), $this->equalTo(false))
            ->will($this->returnValue('{"encoding":"base64","content":"'.base64_encode('{"support": {}}').'"}'));

        $remoteFilesystem->expects($this->at(2))
            ->method('getContents')
            ->with($this->equalTo('github.com'), $this->equalTo('https://api.github.com/repos/composer-test/repo-name/commits/'.$sha), $this->equalTo(false))
            ->will($this->returnValue('{"commit": {"committer":{ "date": "2012-09-10"}}}'));

        return $remoteFilesystem;
    }
}
