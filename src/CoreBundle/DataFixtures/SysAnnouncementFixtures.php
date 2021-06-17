<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\DataFixtures;

use Chamilo\CoreBundle\Entity\SysAnnouncement;
use DateTime;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class SysAnnouncementFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $url = $this->getReference(AccessUserFixtures::ACCESS_URL_REFERENCE);

        $sysAnnouncement = (new SysAnnouncement())
            ->setTitle('Welcome')
            ->setContent('Welcome message')
            ->addRole('ROLE_USER')
            ->setUrl($url)
            ->setDateStart(new DateTime())
            ->setDateEnd(new DateTime('now +30 days'))
        ;
        $manager->persist($sysAnnouncement);
        $manager->flush();
    }
}
