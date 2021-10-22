<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\Career;
use Chamilo\CoreBundle\Entity\Promotion;
use Chamilo\CoreBundle\Entity\SysAnnouncement;
use Chamilo\CoreBundle\Repository\SysAnnouncementRepository;
use Chamilo\Tests\ChamiloTestTrait;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SysAnnouncementRepositoryTest extends WebTestCase
{
    use ChamiloTestTrait;

    /**
     * Class SysAnnouncementFixtures created one announcement during installation.
     */
    public function testWelcomeSysAnnouncement(): void
    {
        self::bootKernel();
        $repo = self::getContainer()->get(SysAnnouncementRepository::class);
        $count = $repo->count([]);

        $this->assertSame(1, $count);
    }

    public function testCreate(): void
    {
        self::bootKernel();
        $repo = self::getContainer()->get(SysAnnouncementRepository::class);
        $count = $repo->count([]);
        $this->assertSame(1, $count);

        $em = $this->getEntityManager();

        $sysAnnouncement = (new SysAnnouncement())
            ->setTitle('Welcome to Chamilo!')
            ->setContent('content')
            ->setLang('lang')
            ->setUrl($this->getAccessUrl())
            ->setDateStart(new DateTime())
            ->setDateEnd(new DateTime('now +30 days'))
            ->setRoles(['ROLE_ANOTHER'])
            ->addRole('ROLE_ANONYMOUS')
            ->addRole('ROLE_USER') // connected users
        ;
        $em->persist($sysAnnouncement);
        $em->flush();

        $repo->update($sysAnnouncement);

        $this->assertNotNull($sysAnnouncement->getDateStart());
        $this->assertNotNull($sysAnnouncement->getDateEnd());
        $this->assertNotNull($sysAnnouncement->getLang());
        $this->assertCount(3, $sysAnnouncement->getRoles());
        $this->assertTrue($sysAnnouncement->isVisible());
        $this->assertSame(2, $repo->count([]));

        $repo->delete($sysAnnouncement->getId());
        $this->assertSame(1, $repo->count([]));
    }

    public function testGetVisibilityList(): void
    {
        self::bootKernel();
        $repo = self::getContainer()->get(SysAnnouncementRepository::class);
        $this->assertIsArray($repo->getVisibilityList());
    }

    public function testGetAnnouncements(): void
    {
        self::bootKernel();

        $em = $this->getEntityManager();
        $repo = self::getContainer()->get(SysAnnouncementRepository::class);
        $user = $this->getUser('admin');
        $items = $repo->getAnnouncements($user, $this->getAccessUrl(), '');
        $this->assertCount(1, $items);

        $career = (new Career())
            ->setName('Doctor')
        ;
        $em->persist($career);
        $promotion = (new Promotion())
            ->setName('2000')
            ->setDescription('Promotion of 2000')
            ->setCareer($career)
            ->setStatus(1)
        ;
        $em->persist($promotion);
        $em->flush();

        $sysAnnouncement = (new SysAnnouncement())
            ->setTitle('Welcome to Chamilo!')
            ->setContent('content')
            ->setUrl($this->getAccessUrl())
            ->setDateStart(new DateTime())
            ->setDateEnd(new DateTime('now +30 days'))
            ->setCareer($career)
            ->setPromotion($promotion)
            ->addRole('ROLE_ANONYMOUS')
            ->addRole('ROLE_USER') // connected users
        ;
        $em->persist($sysAnnouncement);
        $em->flush();

        $items = $repo->getAnnouncements($user, $this->getAccessUrl(), '');
        $this->assertSame(1, \count($items));
    }
}
