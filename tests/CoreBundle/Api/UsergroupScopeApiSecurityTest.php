<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Api;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\Usergroup;
use Chamilo\CoreBundle\Entity\UsergroupRelUser;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Component\HttpFoundation\Response;

/**
 * Regression test for the scope fix on /api/usergroups/search and
 * /api/usergroup/{id}.
 *
 * Before the fix both operations were gated only by is_granted('ROLE_USER')
 * and their queries filtered nothing, so any authenticated user could list
 * every usergroup of the installation — administrative classes and other
 * portals' groups included — and read any single group by its numeric id.
 *
 * The search now returns social groups of the current portal only, and the
 * detail operation rejects a non-member of a closed group with 403. An open
 * group stays readable by a non-member, as main/social/group_view.php did in
 * 1.11.x, so that they can still reach it and ask to join.
 *
 * The cross-portal case is NOT covered here: the test database carries a
 * single access URL, so AccessUrlHelper::isMultiple() is false and the portal
 * branch never runs. Verify it by hand on a multi-URL installation.
 */
class UsergroupScopeApiSecurityTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testSearchHidesAdministrativeClasses(): void
    {
        $socialGroup = $this->createSocialGroup(
            'scope_search_social',
            Usergroup::GROUP_PERMISSION_OPEN
        );
        $class = $this->createGroup(
            'scope_search_class',
            Usergroup::NORMAL_CLASS,
            Usergroup::GROUP_PERMISSION_CLOSED
        );

        $student = $this->createUser('scope_search_student');
        $token = $this->getUserTokenFromUser($student);

        // No "search" filter at all: the empty term becomes LIKE '%%', which is
        // exactly how the whole installation used to leak.
        $response = $this
            ->createClientWithCredentials($token)
            ->request('GET', '/api/usergroups/search')
        ;

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $titles = array_column($response->toArray()['hydra:member'], 'title');
        $this->assertContains($socialGroup->getTitle(), $titles);
        $this->assertNotContains($class->getTitle(), $titles);
    }

    public function testNonMemberIsForbiddenOnClosedGroup(): void
    {
        $group = $this->createSocialGroup(
            'scope_detail_closed',
            Usergroup::GROUP_PERMISSION_CLOSED
        );

        $outsider = $this->createUser('scope_detail_outsider');
        $token = $this->getUserTokenFromUser($outsider);

        $this
            ->createClientWithCredentials($token)
            ->request('GET', '/api/usergroup/'.$group->getId())
        ;

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testNonMemberIsForbiddenOnAdministrativeClass(): void
    {
        $class = $this->createGroup(
            'scope_detail_class',
            Usergroup::NORMAL_CLASS,
            Usergroup::GROUP_PERMISSION_CLOSED
        );

        $outsider = $this->createUser('scope_detail_class_outsider');
        $token = $this->getUserTokenFromUser($outsider);

        $this
            ->createClientWithCredentials($token)
            ->request('GET', '/api/usergroup/'.$class->getId())
        ;

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testNonMemberStillReadsAnOpenGroup(): void
    {
        $group = $this->createSocialGroup(
            'scope_detail_open',
            Usergroup::GROUP_PERMISSION_OPEN
        );

        $outsider = $this->createUser('scope_detail_open_outsider');
        $token = $this->getUserTokenFromUser($outsider);

        $response = $this
            ->createClientWithCredentials($token)
            ->request('GET', '/api/usergroup/'.$group->getId())
        ;

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertSame($group->getTitle(), $response->toArray()['title']);
    }

    public function testMemberReadsItsClosedGroup(): void
    {
        $group = $this->createSocialGroup(
            'scope_detail_member_group',
            Usergroup::GROUP_PERMISSION_CLOSED
        );

        $member = $this->createUser('scope_detail_member');
        $this->addReader($member, $group);

        $token = $this->getUserTokenFromUser($member);

        $this
            ->createClientWithCredentials($token)
            ->request('GET', '/api/usergroup/'.$group->getId())
        ;

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testAdminReadsAnyGroup(): void
    {
        $class = $this->createGroup(
            'scope_detail_admin_class',
            Usergroup::NORMAL_CLASS,
            Usergroup::GROUP_PERMISSION_CLOSED
        );

        $admin = $this->createUser('scope_detail_admin', '', '', 'ROLE_ADMIN');
        $token = $this->getUserTokenFromUser($admin);

        $this
            ->createClientWithCredentials($token)
            ->request('GET', '/api/usergroup/'.$class->getId())
        ;

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    private function createSocialGroup(string $title, int $visibility): Usergroup
    {
        return $this->createGroup($title, Usergroup::SOCIAL_CLASS, $visibility);
    }

    /**
     * ChamiloTestTrait::createUserGroup() creates its own "usergroup_creator"
     * user, so a test building two groups hits a unique-username violation.
     */
    private function createGroup(string $title, int $groupType, int $visibility): Usergroup
    {
        $group = (new Usergroup())
            ->setTitle($title)
            ->setDescription('scope test')
            ->setGroupType($groupType)
            ->setVisibility((string) $visibility)
            ->setAllowMembersToLeaveGroup(1)
            ->setCreator($this->getAdmin())
            ->addAccessUrl($this->getAccessUrl())
        ;

        $em = $this->getEntityManager();
        $em->persist($group);
        $em->flush();

        return $group;
    }

    /**
     * UsergroupRepository::addUserToGroup() downgrades a closed group to
     * PENDING_INVITATION, which the voter does not read as membership, so the
     * relation is written directly.
     */
    private function addReader(User $user, Usergroup $group): void
    {
        $relation = (new UsergroupRelUser())
            ->setUsergroup($group)
            ->setUser($user)
            ->setRelationType(Usergroup::GROUP_USER_PERMISSION_READER)
        ;

        $em = $this->getEntityManager();
        $em->persist($relation);
        $em->flush();
    }
}
