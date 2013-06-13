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
use Symfony\Component\Icu\IcuLanguageBundle;

/**
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class IcuLanguageBundleTest extends IcuTestCase
{
    /**
     * @var string
     */
    private $resDir;

    /**
     * @var IcuLanguageBundle
     */
    private $bundle;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $reader;

    protected function setUp()
    {
        parent::setUp();

        $this->resDir = IcuData::getResourceDirectory() . '/lang';
        $this->reader = $this->getMock('Symfony\Component\Intl\ResourceBundle\Reader\StructuredBundleReaderInterface');
        $this->bundle = new IcuLanguageBundle($this->reader);
    }

    public function testGetLanguageName()
    {
        $languages = array(
            'de' => 'German',
            'en' => 'English',
        );

        $this->reader->expects($this->once())
            ->method('readEntry')
            ->with($this->resDir, 'en', array('Languages'))
            ->will($this->returnValue($languages));

        $this->assertSame('German', $this->bundle->getLanguageName('de', null, 'en'));
    }

    public function testGetLanguageNameWithRegion()
    {
        $languages = array(
            'de' => 'German',
            'en' => 'English',
            'en_GB' => 'British English',
        );

        $this->reader->expects($this->once())
            ->method('readEntry')
            ->with($this->resDir, 'en', array('Languages'))
            ->will($this->returnValue($languages));

        $this->assertSame('British English', $this->bundle->getLanguageName('en', 'GB', 'en'));
    }

    public function testGetLanguageNameWithUntranslatedRegion()
    {
        $languages = array(
            'de' => 'German',
            'en' => 'English',
        );

        $this->reader->expects($this->once())
            ->method('readEntry')
            ->with($this->resDir, 'en', array('Languages'))
            ->will($this->returnValue($languages));

        $this->assertSame('English', $this->bundle->getLanguageName('en', 'US', 'en'));
    }

    public function testGetLanguageNameForMultipleLanguages()
    {
        $this->reader->expects($this->never())
            ->method('readEntry');

        $this->assertNull($this->bundle->getLanguageName('mul', 'en'));
    }

    public function testGetLanguageNames()
    {
        $languages = array(
            'mul' => 'Multiple Languages',
            'de' => 'German',
            'en' => 'English',
        );

        $this->reader->expects($this->once())
            ->method('readEntry')
            ->with($this->resDir, 'en', array('Languages'))
            ->will($this->returnValue($languages));

        $sortedLanguages = array(
            'en' => 'English',
            'de' => 'German',
        );

        $this->assertSame($sortedLanguages, $this->bundle->getLanguageNames('en'));
    }

    public function testGetScriptNames()
    {
        $scripts = array(
            'Latn' => 'latin',
            'Cyrl' => 'cyrillique',
        );

        $this->reader->expects($this->once())
            ->method('readEntry')
            ->with($this->resDir, 'en', array('Scripts'))
            ->will($this->returnValue($scripts));

        $sortedScripts = array(
            'Cyrl' => 'cyrillique',
            'Latn' => 'latin',
        );

        $this->assertSame($sortedScripts, $this->bundle->getScriptNames('en'));
    }
}
