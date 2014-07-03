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

namespace JMS\DiExtraBundle\Tests\Finder;

use JMS\DiExtraBundle\Finder\PatternFinder;

class GrepPatternFinderTest extends AbstractPatternFinderTest
{
    protected $disableGrep = false;
    protected $forceMethodReload = false;

    protected function getFinder()
    {
        $finder = new PatternFinder(
            'JMS\DiExtraBundle\Annotation',
            '*.php',
            $this->disableGrep,
            $this->forceMethodReload
        );

        if (!$this->disableGrep) {
            $ref = new \ReflectionProperty($finder, 'grepPath');
            $ref->setAccessible(true);
            if (null === $v = $ref->getValue($finder)) {
                $this->markTestSkipped('grep is not available on your system.');
            }
        }

        return $finder;
    }

    public function testFinderMethodIsNotGrepIfDisableGrepParameterIsSetToTrue()
    {
        // Change the flag to disable grep
        $this->disableGrep = true;
        $this->forceMethodReload = true;

        // Get the finder and a reflection object on its method property
        $finder = $this->getFinder();
        $ref = new \ReflectionProperty($finder, 'method');
        $ref->setAccessible(true);

        // Ensure the method is not grep
        $this->assertNotEquals(PatternFinder::METHOD_GREP, $ref->getValue($finder));
    }
}
