<?php

namespace PhpCoveralls\Tests;

use PHPUnit\Framework\TestCase;

abstract class ProjectTestCase extends TestCase
{
    /**
     * @var string
     */
    protected $rootDir;

    /**
     * @var string
     */
    protected $srcDir;

    /**
     * @var string
     */
    protected $url;

    /**
     * @var string
     */
    protected $filename;

    /**
     * @var string
     */
    protected $buildDir;

    /**
     * @var string
     */
    protected $logsDir;

    /**
     * @var string
     */
    protected $cloverXmlPath;

    /**
     * @var string
     */
    protected $cloverXmlPath1;

    /**
     * @var string
     */
    protected $cloverXmlPath2;

    /**
     * @var string
     */
    protected $jsonPath;

    /**
     * @param string $projectDir
     */
    protected function setUpDir($projectDir)
    {
        $this->rootDir = realpath($projectDir . '/Fixture');
        $this->srcDir = realpath($this->rootDir . '/files');

        $this->url = 'https://coveralls.io/api/v1/jobs';
        $this->filename = 'json_file';

        // build
        $this->buildDir = $this->rootDir . '/build';
        $this->logsDir = $this->rootDir . '/build/logs';

        // log
        $this->cloverXmlPath = $this->logsDir . '/clover.xml';
        $this->cloverXmlPath1 = $this->logsDir . '/clover-part1.xml';
        $this->cloverXmlPath2 = $this->logsDir . '/clover-part2.xml';
        $this->jsonPath = $this->logsDir . '/coveralls-upload.json';
    }

    /**
     * @param string          $srcDir
     * @param string          $logsDir
     * @param string|string[] $cloverXmlPaths
     * @param bool            $logsDirUnwritable
     * @param bool            $jsonPathUnwritable
     */
    protected function makeProjectDir($srcDir = null, $logsDir = null, $cloverXmlPaths = null, $logsDirUnwritable = false, $jsonPathUnwritable = false)
    {
        if ($srcDir !== null && !is_dir($srcDir)) {
            mkdir($srcDir, 0777, true);
        }

        if ($logsDir !== null && !is_dir($logsDir)) {
            mkdir($logsDir, 0777, true);
        }

        if ($cloverXmlPaths !== null) {
            if (is_array($cloverXmlPaths)) {
                foreach ($cloverXmlPaths as $cloverXmlPath) {
                    touch($cloverXmlPath);
                }
            } else {
                touch($cloverXmlPaths);
            }
        }

        if ($logsDirUnwritable && file_exists($logsDir)) {
            chmod($logsDir, 0577);
        }

        if ($jsonPathUnwritable) {
            touch($this->jsonPath);
            chmod($this->jsonPath, 0577);
        }
    }

    /**
     * @param string $file
     */
    protected function rmFile($file)
    {
        if (is_file($file)) {
            // we try to unlock file, for that, we might need different permissions:
            chmod(dirname($file), 0777); // on unix
            chmod($file, 0777); // on Windows
            unlink($file);
        }
    }

    /**
     * @param string $dir
     */
    protected function rmDir($dir)
    {
        if (is_dir($dir)) {
            chmod($dir, 0777);
            rmdir($dir);
        }
    }

    /**
     * @param string $path
     *
     * @return string
     */
    protected function normalizePath($path)
    {
        return strtr(DIRECTORY_SEPARATOR, '/', $path);
    }

    /**
     * @param string $expected
     * @param string $input
     * @param string $msg
     */
    protected function assertSamePath($expected, $input, $msg = null)
    {
        $this->assertSame(
            $this->normalizePath($expected),
            $this->normalizePath($input),
            $msg
        );
    }

    /**
     * @param string[] $expected
     * @param string[] $input
     * @param string   $msg
     */
    protected function assertSamePaths(array $expected, array $input, $msg = null)
    {
        $expected = array_map(function ($path) { return $this->normalizePath($path); }, $expected);
        $input = array_map(function ($path) { return $this->normalizePath($path); }, $input);

        $this->assertSame($expected, $input, $msg);
    }
}
