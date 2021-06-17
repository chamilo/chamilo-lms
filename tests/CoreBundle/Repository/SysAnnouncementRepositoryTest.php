<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\Course;
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
     * Create a course with no creator.
     */
    public function testCreateNoCreator(): void
    {
        self::bootKernel();
        $repo = self::getContainer()->get(SysAnnouncementRepository::class);
        $count = $repo->count([]);

        // SysAnnouncementFixtures created one announcement during installation.
        $this->assertSame(1, $count);
    }
}
