<?php

/*
 * This file is part of the Symfony CMF package.
 *
 * (c) 2011-2014 Symfony CMF
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Cmf\Bundle\RoutingBundle\Tests\Unit\Controller;

use Symfony\Cmf\Bundle\RoutingBundle\Controller\RedirectController;

use Symfony\Cmf\Component\Routing\Test\CmfUnitTestCase;

class RedirectControllerTest extends CmfUnitTestCase
{
    public function testConstructor()
    {
        new RedirectController($this->buildMock('Symfony\Component\Routing\RouterInterface'));
    }

    // the rest is covered by functional test
}
