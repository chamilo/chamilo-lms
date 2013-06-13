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
use Symfony\Component\Icu\IcuLocaleBundle;

/**
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class IcuLocaleBundleTest extends IcuTestCase
{
    /**
     * @var string
     */
    private $resDir;

    /**
     * @var IcuLocaleBundle
     */
    private $bundle;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $reader;

    protected function setUp()
    {
        parent::setUp();

        $this->resDir = IcuData::getResourceDirectory() . '/locales';
        $this->reader = $this->getMock('Symfony\Component\Intl\ResourceBundle\Reader\StructuredBundleReaderInterface');
        $this->bundle = new IcuLocaleBundle($this->reader);
    }

    public function testGetLocaleNames()
    {
        $locales = array(
            'en_GB' => 'English (United Kingdom)',
            'en_IE' => 'English (Ireland)',
            'en_US' => 'English (United States)',
        );

        $this->reader->expects($this->once())
            ->method('readEntry')
            ->with($this->resDir, 'en', array('Locales'))
            ->will($this->returnValue($locales));

        $sortedLocales = array(
            'en_IE' => 'English (Ireland)',
            'en_GB' => 'English (United Kingdom)',
            'en_US' => 'English (United States)',
        );

        $this->assertSame($sortedLocales, $this->bundle->getLocaleNames('en'));
    }
}
