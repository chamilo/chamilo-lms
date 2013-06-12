<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Icu\Tests;

use Symfony\Component\Icu\IcuData;
use Symfony\Component\Intl\Intl;
use Symfony\Component\Intl\Util\IcuVersion;
use Symfony\Component\Intl\Util\Version;

/**
 * Base test case for the Icu component.
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
abstract class IcuTestCase extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        if (!Intl::isExtensionLoaded()) {
            $this->markTestSkipped('The intl extension is not available.');
        }

        if (IcuVersion::compare(Intl::getIcuVersion(), '4.4', '<', $precision = 1)) {
            $this->markTestSkipped('Please change your ICU version to 4.4 or higher');
        }
    }
}
