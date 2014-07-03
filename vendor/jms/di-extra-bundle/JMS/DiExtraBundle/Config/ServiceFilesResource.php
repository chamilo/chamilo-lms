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

class ServiceFilesResource implements ResourceInterface
{
    private $files;
    private $dirs;
    private $disableGrep;

    public function __construct(array $files, array $dirs, $disableGrep)
    {
        $this->files = $files;
        $this->dirs = $dirs;
        $this->disableGrep = $disableGrep;
    }

    public function isFresh($timestamp)
    {
        $finder = new PatternFinder('JMS\DiExtraBundle\Annotation', '*.php', $this->disableGrep);
        $files = $finder->findFiles($this->dirs);

        return !array_diff($files, $this->files) && !array_diff($this->files, $files);
    }

    public function __toString()
    {
        return implode(', ', $this->files);
    }

    public function getResource()
    {
        return array($this->files, $this->dirs);
    }
}
