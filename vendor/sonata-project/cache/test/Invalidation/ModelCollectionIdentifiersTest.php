<?php

/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\Cache\Tests\Invalidation;

use Sonata\Cache\Invalidation\ModelCollectionIdentifiers;

class Model_1
{
    public function getCacheIdentifier()
    {
        return 1;
    }
}

class Model_2
{

    public function getId()
    {
        return 2;
    }
}

class Model_3 extends Model_2
{

    public function getSuperCache()
    {
        return 'super!';
    }
}

class CacheElementTest extends \PHPUnit_Framework_TestCase
{

    public function test()
    {
        $collection = new ModelCollectionIdentifiers(array(
            'Sonata\Cache\Tests\Invalidation\Model_3' => 'getId'
        ));

        $m3 = new Model_3;
        $this->assertEquals('getId', $collection->getMethod($m3));
        $this->assertEquals('2', $collection->getIdentifier($m3));

        $m1 = new Model_1;
        $this->assertEquals('getCacheIdentifier', $collection->getMethod($m1));

        $collection->addClass('Sonata\Cache\Tests\Invalidation\Model_3', 'getSuperCache');

        $this->assertEquals('super!', $collection->getIdentifier($m3));
    }
}
