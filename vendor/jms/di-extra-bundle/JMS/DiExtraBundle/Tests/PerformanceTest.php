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

namespace JMS\DiExtraBundle\Tests;

use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Bundle\DoctrineBundle\DoctrineBundle;
use JMS\DiExtraBundle\JMSDiExtraBundle;
use JMS\SecurityExtraBundle\JMSSecurityExtraBundle;
use Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle;
use Symfony\Bundle\FrameworkBundle\DependencyInjection\FrameworkExtension;
use Symfony\Bundle\AsseticBundle\AsseticBundle;
use Symfony\Bundle\MonologBundle\MonologBundle;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use JMS\DiExtraBundle\Finder\ServiceFinder;

/**
 * @group performance
 */
class PerformanceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getFinderMethods
     */
    public function testServiceFinder($method)
    {
        $finder = new ServiceFinder();
        $ref = new \ReflectionMethod($finder, $method);
        $ref->setAccessible(true);

        $bundles = array(
            new FrameworkBundle(),
            new SecurityBundle(),
            new MonologBundle(),
            new AsseticBundle(),
            new DoctrineBundle(),
            new TwigBundle(),
            new SensioFrameworkExtraBundle(),
            new JMSSecurityExtraBundle(),
        );
        $bundles = array_map(function($v) {
            return $v->getPath();
        }, $bundles);

        $bundles[] = __DIR__.'/../';

        $time = microtime(true);
        for ($i=0,$c=5; $i<$c; $i++) {
            $ref->invoke($finder, $bundles);
        }
        $time = microtime(true) - $time;
        $this->printResults('service finder ('.$method.')', $time, $c);
    }

    public function getFinderMethods()
    {
        return array(
            array('findUsingGrep'),
            array('findUsingFinder'),
        );
    }

    private function printResults($test, $time, $iterations)
    {
        if (0 == $iterations) {
            throw new InvalidArgumentException('$iterations cannot be zero.');
        }

        $title = $test." results:\n";
        $iterationsText = sprintf("Iterations:         %d\n", $iterations);
        $totalTime      = sprintf("Total Time:         %.3f s\n", $time);
        $iterationTime  = sprintf("Time per iteration: %.3f ms\n", $time/$iterations * 1000);

        $max = max(strlen($title), strlen($iterationTime)) - 1;

        echo "\n".str_repeat('-', $max)."\n";
        echo $title;
        echo str_repeat('=', $max)."\n";
        echo $iterationsText;
        echo $totalTime;
        echo $iterationTime;
        echo str_repeat('-', $max)."\n";
    }
}
