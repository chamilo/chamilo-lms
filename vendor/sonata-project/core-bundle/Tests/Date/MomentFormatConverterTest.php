<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CoreBundle\Tests\Date;

use Sonata\CoreBundle\Date\MomentFormatConverter;

/**
 * @author Hugo Briand <briand@ekino.com>
 */
class MomentFormatConverterTest extends \PHPUnit_Framework_TestCase
{
    public function testPhpToMoment()
    {
        $mfc = new MomentFormatConverter();

        $phpFormat = "yyyy-MM-dd'T'HH:mm:ssZZZZZ";
        $this->assertSame('YYYY-MM-DDTHH:mm:ssZ', $mfc->convert($phpFormat));

        $phpFormat = 'yyyy-MM-dd HH:mm:ss';
        $this->assertSame('YYYY-MM-DD HH:mm:ss', $mfc->convert($phpFormat));

        $phpFormat = 'yyyy-MM-dd HH:mm';
        $this->assertSame('YYYY-MM-DD HH:mm', $mfc->convert($phpFormat));

        $phpFormat = 'yyyy-MM-dd';
        $this->assertSame('YYYY-MM-DD', $mfc->convert($phpFormat));

        $phpFormat = 'dd.MM.yyyy, HH:mm';
        $this->assertSame('DD.MM.YYYY, HH:mm', $mfc->convert($phpFormat));

        $phpFormat = 'dd.MM.yyyy, HH:mm:ss';
        $this->assertSame('DD.MM.YYYY, HH:mm:ss', $mfc->convert($phpFormat));

        $phpFormat = 'dd.MM.yyyy';
        $this->assertSame('DD.MM.YYYY', $mfc->convert($phpFormat));

        $phpFormat = 'd.M.yyyy';
        $this->assertSame('D.M.YYYY', $mfc->convert($phpFormat));

        $phpFormat = 'd.M.yyyy HH:mm';
        $this->assertSame('D.M.YYYY HH:mm', $mfc->convert($phpFormat));

        $phpFormat = 'd.M.yyyy HH:mm:ss';
        $this->assertSame('D.M.YYYY HH:mm:ss', $mfc->convert($phpFormat));

        $phpFormat = 'dd/MM/yyyy';
        $this->assertSame('DD/MM/YYYY', $mfc->convert($phpFormat));

        $phpFormat = 'dd/MM/yyyy HH:mm';
        $this->assertSame('DD/MM/YYYY HH:mm', $mfc->convert($phpFormat));

        $phpFormat = 'EE, dd/MM/yyyy HH:mm';
        $this->assertSame('ddd, DD/MM/YYYY HH:mm', $mfc->convert($phpFormat));

        $phpFormat = 'dd-MM-yyyy';
        $this->assertSame('DD-MM-YYYY', $mfc->convert($phpFormat));

        $phpFormat = 'dd-MM-yyyy HH:mm';
        $this->assertSame('DD-MM-YYYY HH:mm', $mfc->convert($phpFormat));

        $phpFormat = 'dd-MM-yyyy HH:mm:ss';
        $this->assertSame('DD-MM-YYYY HH:mm:ss', $mfc->convert($phpFormat));

        $phpFormat = 'dd.MM.y HH:mm:ss';
        $this->assertSame('DD.MM.YYYY HH:mm:ss', $mfc->convert($phpFormat));

        $phpFormat = 'D MMM y';
        $this->assertSame('D MMM YYYY', $mfc->convert($phpFormat));

        $phpFormat = "dd 'de' MMMM 'de' YYYY"; //Brazilian date format
        $this->assertSame('DD [de] MMMM [de] YYYY', $mfc->convert($phpFormat));
    }
}
