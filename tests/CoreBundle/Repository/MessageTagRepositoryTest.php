<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\MessageTag;
use Chamilo\CoreBundle\Repository\MessageTagRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @covers \MessageTagRepository
 */
class MessageTagRepositoryTest extends WebTestCase
{
    use ChamiloTestTrait;

    public function testCreateTag(): void
    {
        self::bootKernel();
        $tagRepo = self::getContainer()->get(MessageTagRepository::class);

        $testUser = $this->createUser('test');

        // Create tag.
        $tag =
            (new MessageTag())
                ->setTag('my tag')
                ->setColor('red')
                ->setUser($testUser)
        ;

        $this->assertHasNoEntityViolations($tag);

        $tagRepo->update($tag);

        $count = $tagRepo->count([]);
        $this->assertSame(1, $count);

        // Delete user, then no tags.
        $userRepo = self::getContainer()->get(UserRepository::class);
        $userRepo->delete($testUser);

        $count = $tagRepo->count([]);
        $this->assertSame(0, $count);
    }
}
