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

namespace JMS\DiExtraBundle\Config;

use JMS\DiExtraBundle\Finder\PatternFinder;

use Symfony\Component\Config\Resource\ResourceInterface;

class FastDirectoriesResource implements ResourceInterface
{
    private $finder;

    private $directories;
    private $filePattern;
    private $files = array();

    public function __construct(array $directories, $filePattern = null, $disableGrep = false)
    {
        $this->finder = new PatternFinder('.*', '*.php', $disableGrep);
        $this->finder->setRegexPattern(true);

        $this->directories = $directories;
        $this->filePattern = $filePattern ?: '*';
    }

    public function __toString()
    {
        return implode(', ', $this->directories);
    }

    public function getResource()
    {
        return $this->directories;
    }

    public function update()
    {
        $this->files = $this->getFiles();
    }

    public function isFresh($timestamp)
    {
        $files = $this->getFiles();

        if (array_diff($this->files, $files) || array_diff($files, $this->files)) {
            return false;
        }

        foreach ($files as $file) {
            if (filemtime($file) > $timestamp) {
                return false;
            }
        }

        return true;
    }

    private function getFiles()
    {
        return $this->finder->findFiles($this->directories);
    }
}
