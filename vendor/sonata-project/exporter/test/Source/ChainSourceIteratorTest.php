<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Exporter\Test\Source;

use Exporter\Source\ChainSourceIterator;

class ChainSourceIteratorTest extends \PHPUnit_Framework_TestCase
{
    public function testIterator()
    {
        $source = $this->getMock('Exporter\Source\SourceIteratorInterface');

        $iterator = new ChainSourceIterator(array($source));

        foreach ($iterator as $data) {
        }
    }
}
