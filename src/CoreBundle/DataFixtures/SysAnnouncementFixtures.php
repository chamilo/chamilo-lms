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

        $content = '<p>
                        <img src="/img/document/images/mr_chamilo/svg/collaborative.svg" width="320" height="340" />
                    </p>
                    Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore
                    et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut
                    aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit
                    esse cillum dolore eu fugiat nulla pariatur.
                    ';

        $sysAnnouncement = (new SysAnnouncement())
            ->setTitle('Welcome to Chamilo!')
            ->setContent($content)
            ->setUrl($url)
            ->setDateStart(new DateTime())
            ->setDateEnd(new DateTime('now +30 days'))
            ->addRole('ROLE_ANONYMOUS')
        ;
        $manager->persist($sysAnnouncement);
        $manager->flush();
    }
}
