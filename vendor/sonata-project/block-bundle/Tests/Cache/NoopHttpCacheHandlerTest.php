<?php

/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\BlockBundle\Tests\Cache;

use Sonata\BlockBundle\Cache\NoopHttpCacheHandler;

class NoopHttpCacheHandlerTest extends \PHPUnit_Framework_TestCase
{
    public function testComputeTtl()
    {
        // check interface
        new NoopHttpCacheHandler();
    }
}