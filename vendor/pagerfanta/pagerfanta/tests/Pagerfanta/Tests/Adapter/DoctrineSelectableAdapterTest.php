<?php

namespace Pagerfanta\Tests\Adapter;

use Pagerfanta\Adapter\DoctrineSelectableAdapter;
use Doctrine\Common\Collections\Selectable;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Version;

class DoctrineSelectableAdapterTest extends \PHPUnit_Framework_TestCase
{
    private $selectable;
    private $criteria;
    private $adapter;

    protected function setUp()
    {
        if ($this->isDoctrine23OrGreaterNotAvailable()) {
            $this->markTestSkipped('This test can only be run using Doctrine >= 2.3');
        }

        $this->selectable = $this->createSelectableMock();
        $this->criteria = $this->createCriteria();

        $this->adapter = new DoctrineSelectableAdapter($this->selectable, $this->criteria);
    }

    private function isDoctrine23OrGreaterNotAvailable()
    {
        return version_compare(Version::VERSION, '2.3', '<');
    }

    private function createSelectableMock()
    {
        return $this->getMock('Doctrine\Common\Collections\Selectable');
    }

    private function createCriteria()
    {
        $criteria = new Criteria();
        $criteria->orderBy(array('username' => 'ASC'));
        $criteria->setFirstResult(2);
        $criteria->setMaxResults(3);

        return $criteria;
    }

    public function testGetNbResults()
    {
        $this->criteria->setFirstResult(null);
        $this->criteria->setMaxResults(null);

        $collection = $this->createCollectionMock();
        $collection
            ->expects($this->any())
            ->method('count')
            ->will($this->returnValue(10));

        $this->selectable
            ->expects($this->once())
            ->method('matching')
            ->with($this->equalTo($this->criteria))
            ->will($this->returnValue($collection));

        $this->assertSame(10, $this->adapter->getNbResults());
    }

    private function createCollectionMock()
    {
        return $this->getMock('Doctrine\Common\Collections\Collection');
    }

    public function testGetSlice()
    {
        $this->criteria->setFirstResult(10);
        $this->criteria->setMaxResults(20);

        $slice = new \stdClass();

        $this->selectable
            ->expects($this->once())
            ->method('matching')
            ->with($this->equalTo($this->criteria))
            ->will($this->returnValue($slice));

        $this->assertSame($slice, $this->adapter->getSlice(10, 20));
    }
}
