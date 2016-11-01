<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CoreBundle\Tests\Twig\Extension;

use Sonata\CoreBundle\Twig\Extension\TemplateExtension;

class TemplateExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @group legacy
     */
    public function testSlugify()
    {
        setlocale(LC_ALL, 'en_US.utf8');
        setlocale(LC_CTYPE, 'en_US.utf8');

        $translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');

        $adapter = $this->getMock('Sonata\CoreBundle\Model\Adapter\AdapterInterface');
        $adapter->expects($this->never())->method('getUrlsafeIdentifier');

        $extension = new TemplateExtension(true, $translator, $adapter);

        $this->assertSame($extension->slugify('test'), 'test');
        $this->assertSame($extension->slugify('S§!@@#$#$alut'), 's-alut');
        $this->assertSame($extension->slugify('Symfony2'), 'symfony2');
        $this->assertSame($extension->slugify('test'), 'test');
        $this->assertSame($extension->slugify('c\'est bientôt l\'été'), 'c-est-bientot-l-ete');
        $this->assertSame($extension->slugify(urldecode('%2Fc\'est+bientôt+l\'été')), 'c-est-bientot-l-ete');
    }

    public function testSafeUrl()
    {
        $translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');

        $adapter = $this->getMock('Sonata\CoreBundle\Model\Adapter\AdapterInterface');
        $adapter->expects($this->once())->method('getUrlsafeIdentifier')->will($this->returnValue('safe-parameter'));

        $extension = new TemplateExtension(true, $translator, $adapter);

        $this->assertSame('safe-parameter', $extension->getUrlsafeIdentifier(new \stdClass()));
    }
}
