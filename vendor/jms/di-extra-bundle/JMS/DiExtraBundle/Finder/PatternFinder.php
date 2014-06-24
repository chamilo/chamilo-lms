<?php

/*
 * Copyright 2011 Johannes M. Schmitt <schmittjoh@gmail.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace JMS\DiExtraBundle\Finder;

use JMS\DiExtraBundle\Exception\RuntimeException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\ExecutableFinder;

class PatternFinder
{
    const METHOD_GREP = 1;
    const METHOD_FINDSTR = 2;
    const METHOD_FINDER = 3;

    private static $method;
    private static $grepPath;

    private $pattern;
    private $filePattern;
    private $recursive = true;
    private $regexPattern = false;

    public function __construct($pattern, $filePattern = '*.php', $disableGrep = false, $forceMethodReload = false)
    {
        if (null === self::$method || $forceMethodReload) {
            self::determineMethod($disableGrep);
        }

        $this->pattern = $pattern;
        $this->filePattern = $filePattern;
    }

    public function setRecursive($bool)
    {
        $this->recursive = (Boolean) $bool;
    }

    public function setRegexPattern($bool)
    {
        $this->regexPattern = (Boolean) $bool;
    }

    public function findFiles(array $dirs)
    {
        // check for grep availability
        if (self::METHOD_GREP === self::$method) {
            return $this->findUsingGrep($dirs);
        }

        // use FINDSTR on Windows
        if (self::METHOD_FINDSTR === self::$method) {
            return $this->findUsingFindstr($dirs);
        }

        // this should really be avoided at all costs since it is damn slow
        return $this->findUsingFinder($dirs);
    }

    private function findUsingFindstr(array $dirs)
    {
        $cmd = 'FINDSTR /M /S /P';

        if (!$this->recursive) {
            $cmd .= ' /L';
        }

        $cmd .= ' /D:'.escapeshellarg(implode(';', $dirs));
        $cmd .= ' '.escapeshellarg($this->pattern);
        $cmd .= ' '.$this->filePattern;

        exec($cmd, $lines, $exitCode);

        if (1 === $exitCode) {
            return array();
        }

        if (0 !== $exitCode) {
            throw new RuntimeException(sprintf('Command "%s" exited with non-successful status code. "%d".', $cmd, $exitCode));
        }

        // Looks like FINDSTR has different versions with different output formats.
        //
        // Supported format #1:
        //     C:\matched\dir1:
        // Relative\Path\To\File1.php
        // Relative\Path\To\File2.php
        //     C:\matched\dir2:
        // Relative\Path\To\File3.php
        // Relative\Path\To\File4.php
        //
        // Supported format #2:
        // C:\matched\dir1\Relative\Path\To\File1.php
        // C:\matched\dir1\Relative\Path\To\File2.php
        // C:\matched\dir2\Relative\Path\To\File3.php
        // C:\matched\dir2\Relative\Path\To\File4.php

        $files = array();
        $currentDir = '';
        foreach ($lines as $line) {
            if (':' === substr($line, -1)) {
                $currentDir = trim($line, ' :/').'/';
                continue;
            }

            $files[] = realpath($currentDir.$line);
        }

        return $files;
    }

    private function findUsingGrep(array $dirs)
    {
        $cmd = self::$grepPath;

        if (!$this->regexPattern) {
            $cmd .= ' --fixed-strings';
        } else {
            $cmd .= ' --extended-regexp';
        }

        if ($this->recursive) {
            $cmd .= ' --directories=recurse';
        } else {
            $cmd .= ' --directories=skip';
        }

        $cmd .= ' --devices=skip --files-with-matches --with-filename --color=never --include='.$this->filePattern;
        $cmd .= ' '.escapeshellarg($this->pattern);

        foreach ($dirs as $dir) {
            $cmd .= ' '.escapeshellarg($dir);
        }
        exec($cmd, $files, $exitCode);

        if (1 === $exitCode) {
            return array();
        }

        if (0 !== $exitCode) {
            throw new RuntimeException(sprintf('Command "%s" exited with non-successful status code "%d".', $cmd, $exitCode));
        }

        return array_map('realpath', $files);
    }

    private function findUsingFinder(array $dirs)
    {
        $finder = new Finder();
        $pattern = $this->pattern;
        $regex = $this->regexPattern;
        $finder
            ->files()
            ->name($this->filePattern)
            ->in($dirs)
            ->ignoreVCS(true)
            ->filter(function($file) use ($pattern, $regex) {
                if (!$regex) {
                    return false !== strpos(file_get_contents($file->getPathName()), $pattern);
                }

                return 0 < preg_match('#'.$pattern.'#', file_get_contents($file->getPathName()));
            })
        ;

        if (!$this->recursive) {
            $finder->depth('<= 0');
        }

        return array_map('realpath', array_keys(iterator_to_array($finder)));
    }

    private static function determineMethod($disableGrep)
    {
        $finder = new ExecutableFinder();
        $isWindows = 0 === stripos(PHP_OS, 'win');
        $execAvailable = function_exists('exec');

        if (!$isWindows && $execAvailable && !$disableGrep && self::$grepPath = $finder->find('grep')) {
            self::$method = self::METHOD_GREP;
        } else if ($isWindows && $execAvailable) {
            self::$method = self::METHOD_FINDSTR;
        } else {
            self::$method = self::METHOD_FINDER;
        }
    }
}
