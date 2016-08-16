<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CoreBundle\Tests\Entity;

use Sonata\CoreBundle\Model\BaseDocumentManager;

class DocumentManager extends BaseDocumentManager
{
}

class BaseDocumentManagerTest extends \PHPUnit_Framework_TestCase
{
    public function getManager()
    {
        $registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');

        $manager = new DocumentManager('classname', $registry);

        return $manager;
    }

    public function test()
    {
        $this->assertSame('classname', $this->getManager()->getClass());
    }
}
