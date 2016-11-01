<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CoreBundle\Tests\Component;

use Sonata\CoreBundle\Component\NativeSlugify;

/**
 * @group legacy
 */
class NativeSlugifyTest extends \PHPUnit_Framework_TestCase
{
    public function testSlugify()
    {
        setlocale(LC_ALL, 'en_US.utf8');
        setlocale(LC_CTYPE, 'en_US.utf8');

        $service = new NativeSlugify();

        $this->assertSame($service->slugify('test'), 'test');
        $this->assertSame($service->slugify('S§!@@#$#$alut'), 's-alut');
        $this->assertSame($service->slugify('Symfony2'), 'symfony2');
        $this->assertSame($service->slugify('test'), 'test');
        $this->assertSame($service->slugify('c\'est bientôt l\'été'), 'c-est-bientot-l-ete');
        $this->assertSame($service->slugify(urldecode('%2Fc\'est+bientôt+l\'été')), 'c-est-bientot-l-ete');
    }
}
