<?php
/**
 * This file is part of the PHPExiftool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Test\Driver;

use PHPExiftool\Driver\TagProvider;

class TagProviderTest extends \PHPUnit_Framework_TestCase
{
    private $object;
    protected function setUp()
    {
        $this->object = new TagProvider;
    }

    public function testGetAll()
    {
        $this->assertInternalType('array', $this->object->getAll());
    }

    public function testGetLookupTable()
    {
        $this->assertInternalType('array', $this->object->getLookupTable());
    }
}
