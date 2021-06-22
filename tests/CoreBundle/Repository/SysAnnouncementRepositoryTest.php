<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Repository;

use Chamilo\CoreBundle\Repository\SysAnnouncementRepository;
use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @covers \SysAnnouncementRepository
 */
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
}
