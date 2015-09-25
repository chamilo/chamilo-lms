<?php

/*
 * This file is part of the Fxp Composer Asset Plugin package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Composer\AssetPlugin\Tests\Event;

use Fxp\Composer\AssetPlugin\AssetEvents;
use Fxp\Composer\AssetPlugin\Event\VcsRepositoryEvent;

/**
 * Tests for the vcs repository event.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class VcsRepositoryEventTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getTestEvents
     */
    public function testEvents($eventName, array $repos)
    {
        $event = new VcsRepositoryEvent($eventName, $repos);

        $this->assertSame($eventName, $event->getName());
        $this->assertSame(array(
            array('type' => 'TYPE', 'url' => 'URL'),
        ), $event->getRepositories());
    }

    public function getTestEvents()
    {
        return array(
            array(AssetEvents::ADD_VCS_REPOSITORIES, array(
                array('type' => 'TYPE', 'url' => 'URL'),
            )),
        );
    }
}
