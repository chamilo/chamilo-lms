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

abstract class AbstractPatternFinderTest extends \PHPUnit_Framework_TestCase
{
    public function testFindFiles()
    {
        $finder = $this->getFinder();

        $expectedFiles = array(
            realpath(__DIR__.'/../Fixture/NonEmptyDirectory/Service1.php'),
            realpath(__DIR__.'/../Fixture/NonEmptyDirectory/SubDir1/Service2.php'),
            realpath(__DIR__.'/../Fixture/NonEmptyDirectory/SubDir2/Service3.php'),
        );

        $foundFiles = $finder->findFiles(array(__DIR__.'/../Fixture/NonEmptyDirectory'));

        $this->assertEquals(array(), array_diff($expectedFiles, $foundFiles));
        $this->assertEquals(array(), array_diff($foundFiles, $expectedFiles));
    }

    public function testFindFilesUsingGrepReturnsEmptyArrayWhenNoMatchesAreFound()
    {
        $finder = $this->getFinder();
        $this->assertEquals(array(), $finder->findFiles(array(__DIR__.'/../Fixture/EmptyDirectory')));
    }

    abstract protected function getFinder();
}
