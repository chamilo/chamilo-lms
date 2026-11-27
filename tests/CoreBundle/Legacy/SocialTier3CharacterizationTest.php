<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Entity\UserRelUser;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\Tests\ChamiloTestTrait;
use ChamiloSession;
use SocialManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session as HttpSession;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * get_relation_between_contacts() when one pair holds several rows (the unique key is user, friend AND type, so a pair can
 * be "friend request" and "friend" at once). The answer is the LOWEST type stored that exists in user_friend_relation_type
 * (ids 1..6); verified against the former raw query with LIMIT 1 in a subquery.
 */
final class SocialTier3CharacterizationTest extends KernelTestCase
{
    use ChamiloTestTrait;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        Container::setContainer($container);
        Container::setLegacyServices($container);
        $httpSession = new HttpSession(new MockArraySessionStorage());
        Container::setSession($httpSession);
        $request = Request::create('/main/social/home.php');
        $request->setSession($httpSession);
        Container::setRequest($request);
        ChamiloSession::erase('_user');
    }

    public function testSeveralRowsOfOnePairAnswerTheLowestKnownType(): void
    {
        foreach ([UserRelUser::USER_RELATION_TYPE_FRIEND_REQUEST, UserRelUser::USER_RELATION_TYPE_FRIEND, UserRelUser::USER_RELATION_TYPE_RRHH] as $type) {
            $this->getEntityManager()->getConnection()->executeStatement(
                "INSERT INTO user_rel_user (user_id, friend_user_id, relation_type, created_at, updated_at) VALUES (1, 2, ?, '2030-01-01 00:00:00', '2030-01-01 00:00:00')",
                [$type]
            );
        }

        $this->assertSame(UserRelUser::USER_RELATION_TYPE_FRIEND, SocialManager::get_relation_between_contacts(1, 2));
        $this->assertSame(UserRelUser::USER_RELATION_TYPE_FRIEND, SocialManager::get_relation_between_contacts(1, 2, true));
        $this->assertSame(UserRelUser::USER_UNKNOWN, SocialManager::get_relation_between_contacts(2, 1));
    }
}
