<?php

/*
 * This file is part of the Symfony CMF package.
 *
 * (c) 2011-2014 Symfony CMF
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Cmf\Bundle\RoutingBundle\Tests\Unit\Doctrine\Orm;

use Symfony\Cmf\Bundle\RoutingBundle\Doctrine\Orm\ContentRepository;

class ContentRepositoryTest extends \PHPUnit_Framework_TestCase
{
    private $document;
    private $managerRegistry;
    private $objectManager;
    private $objectRepository;

    public function setUp()
    {
        $this->document = new \stdClass;
        $this->objectManager = $this->getMock('Doctrine\Common\Persistence\ObjectManager');
        $this->managerRegistry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $this->objectRepository = $this->getMock('Doctrine\Common\Persistence\ObjectRepository');
    }

    public function testFindById()
    {
        $this->objectManager
            ->expects($this->any())
            ->method('getRepository')
            ->with($this->equalTo('stdClass'))
            ->will($this->returnValue($this->objectRepository))
        ;

        $this->objectRepository
            ->expects($this->any())
            ->method('find')
            ->with(123)
            ->will($this->returnValue($this->document))
        ;

        $this->managerRegistry
            ->expects($this->any())
            ->method('getManager')
            ->will($this->returnValue($this->objectManager))
        ;

        $contentRepository = new ContentRepository($this->managerRegistry);
        $contentRepository->setManagerName('default');

        $foundDocument = $contentRepository->findById('stdClass:123');

        $this->assertSame($this->document, $foundDocument);
    }

    /**
     * @dataProvider getFindCorrectModelAndIdData
     */
    public function testFindCorrectModelAndId($input, $model, $id)
    {
        $this->objectManager
            ->expects($this->any())
            ->method('getRepository')
            ->with($this->equalTo($model))
            ->will($this->returnValue($this->objectRepository))
        ;

        $this->objectRepository
            ->expects($this->any())
            ->method('find')
            ->with($id)
        ;

        $this->managerRegistry
            ->expects($this->any())
            ->method('getManager')
            ->will($this->returnValue($this->objectManager))
        ;

        $contentRepository = new ContentRepository($this->managerRegistry);
        $contentRepository->setManagerName('default');

        $foundDocument = $contentRepository->findById($input);
    }

    public function getFindCorrectModelAndIdData()
    {
        return array(
            array('Acme\ContentBundle\Entity\Content:12', 'Acme\ContentBundle\Entity\Content', 12),
            array('Id\Contains\Colon:12:1', 'Id\Contains\Colon', '12:1'),
            array('Class\EndsWith\Number12:20', 'Class\EndsWith\Number12', 20),
        );
    }
}
