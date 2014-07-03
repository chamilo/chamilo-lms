<?php

/*
 * This file is part of the Symfony CMF package.
 *
 * (c) 2011-2013 Symfony CMF
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Cmf\Bundle\RoutingBundle\Tests\Unit\Doctrine\Phpcr;

use Symfony\Cmf\Bundle\RoutingBundle\Doctrine\Phpcr\PrefixCandidates;
use Symfony\Cmf\Component\Routing\Test\CmfUnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

class PrefixCandidatesTest extends CmfUnitTestCase
{
    public function testAddPrefix()
    {
        $candidates = new PrefixCandidates(array('/routes'));
        $this->assertEquals(array('/routes'), $candidates->getPrefixes());
        $candidates->addPrefix('/simple');
        $this->assertEquals(array('/routes', '/simple'), $candidates->getPrefixes());
        $candidates->setPrefixes(array('/other'));
        $this->assertEquals(array('/other'), $candidates->getPrefixes());
    }

    public function testGetCandidates()
    {
        $request = Request::create('/my/path.html');

        $candidates = new PrefixCandidates(array('/routes', '/simple'));
        $paths = $candidates->getCandidates($request);

        $this->assertEquals(
            array(
                '/routes/my/path.html',
                '/routes/my/path',
                '/routes/my',
                '/routes',
                '/simple/my/path.html',
                '/simple/my/path',
                '/simple/my',
                '/simple',
            ),
            $paths
        );
    }

    public function testGetCandidatesLocales()
    {
        $request = Request::create('/de/path.html');

        $candidates = new PrefixCandidates(array('/routes', '/simple'), array('de', 'fr'));
        $paths = $candidates->getCandidates($request);

        $this->assertEquals(
            array(
                '/routes/de/path.html',
                '/routes/de/path',
                '/routes/de',
                '/routes',
                '/simple/de/path.html',
                '/simple/de/path',
                '/simple/de',
                '/simple',
                '/routes/path.html',
                '/routes/path',
                '/simple/path.html',
                '/simple/path',
            ),
            $paths
        );
    }

    public function testGetCandidatesLocalesDm()
    {
        $request = Request::create('/de/path.html');

        $dmMock = $this->buildMock('Doctrine\ODM\PHPCR\DocumentManager');
        $managerRegistryMock = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $managerRegistryMock
            ->expects($this->any())
            ->method('getManager')
            ->will($this->returnValue($dmMock))
        ;
        $localeMock = $this->buildMock('Doctrine\ODM\PHPCR\Translation\LocaleChooser\LocaleChooserInterface');
        $localeMock
            ->expects($this->once())
            ->method('setLocale')
            ->with('de')
        ;
        $dmMock
            ->expects($this->once())
            ->method('getLocaleChooserStrategy')
            ->will($this->returnValue($localeMock))
        ;

        $candidates = new PrefixCandidates(array('/simple'), array('de', 'fr'), $managerRegistryMock);
        $candidates->getCandidates($request);
    }

    public function testGetCandidatesLocalesDmNoLocale()
    {
        $request = Request::create('/it/path.html');
        $managerRegistryMock = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $managerRegistryMock
            ->expects($this->never())
            ->method('getManager')
        ;

        $candidates = new PrefixCandidates(array('/simple'), array('de', 'fr'), $managerRegistryMock);
        $candidates->getCandidates($request);
    }

    public function testIsCandidate()
    {
        $candidates = new PrefixCandidates(array('/routes'));
        $this->assertTrue($candidates->isCandidate('/routes'));
        $this->assertTrue($candidates->isCandidate('/routes/my/path'));
        $this->assertFalse($candidates->isCandidate('/other/my/path'));
        $this->assertFalse($candidates->isCandidate('/route'));
        $this->assertFalse($candidates->isCandidate('/routesnotsame'));
    }

    public function testRestrictQuery()
    {
        $orX = $this->getMock('Doctrine\ODM\PHPCR\Query\Builder\QueryBuilder', array('descendant'));
        $orX->expects($this->once())
            ->method('descendant')
            ->with('/routes', 'd')
        ;
        $andWhere = $this->getMock('Doctrine\ODM\PHPCR\Query\Builder\QueryBuilder', array('orX'));
        $andWhere->expects($this->once())
            ->method('orX')
            ->will($this->returnValue($orX))
        ;
        $qb = $this->getMock('Doctrine\ODM\PHPCR\Query\Builder\QueryBuilder', array('andWhere', 'getPrimaryAlias'));
        $qb->expects($this->once())
            ->method('andWhere')
            ->will($this->returnValue($andWhere))
        ;
        $qb->expects($this->once())
            ->method('getPrimaryAlias')
            ->will($this->returnValue('d'))
        ;

        $candidates = new PrefixCandidates(array('/routes'));
        $candidates->restrictQuery($qb);
    }

    public function testRestrictQueryGlobal()
    {
        $qb = $this->getMock('Doctrine\ODM\PHPCR\Query\Builder\QueryBuilder', array('andWhere'));
        $qb->expects($this->never())
            ->method('andWhere')
        ;

        $candidates = new PrefixCandidates(array('/routes', '', '/other'));
        $candidates->restrictQuery($qb);
    }
}
