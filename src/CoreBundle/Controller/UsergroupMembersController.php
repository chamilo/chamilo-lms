<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\Usergroup;
use Chamilo\CoreBundle\Entity\UsergroupRelCourse;
use Chamilo\CoreBundle\Entity\UsergroupRelSession;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Helpers\IsAllowedToEditHelper;
use Chamilo\CoreBundle\Helpers\NameConventionHelper;
use Chamilo\CoreBundle\Repository\Node\UsergroupRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * One page of a class' members, for the "class members" preview of the admin class list and of the
 * course Users > Classes screen.
 *
 * Who may see the members:
 * - platform admins, and session admins unless session.limit_session_admin_role is on (the rules of
 *   UserGroupModel::protectScript() for the class admin pages);
 * - with ?cid= (and optionally &sid=), whoever may edit that course (api_is_allowed_to_edit(), the
 *   rule of the course class list), but only for a class linked to that course or, inside a session,
 *   to that session.
 *
 * Only the name and the username of the members are returned, as in those lists.
 */
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class UsergroupMembersController extends AbstractController
{
    private const MAX_PAGE_SIZE = 50;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UsergroupRepository $usergroupRepository,
        private readonly AccessUrlHelper $accessUrlHelper,
        private readonly IsAllowedToEditHelper $isAllowedToEditHelper,
        private readonly SettingsManager $settingsManager,
        private readonly NameConventionHelper $nameConventionHelper,
    ) {}

    #[Route('/usergroup-members-data/{id}', name: 'chamilo_core_usergroup_members_data', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function members(Request $request, int $id): JsonResponse
    {
        $usergroup = $this->em->find(Usergroup::class, $id);
        if (null === $usergroup || !$this->belongsToCurrentUrl($usergroup)) {
            return $this->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
        }

        if (!$this->canSeeMembers($usergroup, $request)) {
            return $this->json(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        $page = max(1, $request->query->getInt('page', 1));
        $limit = max(1, min(self::MAX_PAGE_SIZE, $request->query->getInt('limit', 10)));
        $groupId = (int) $usergroup->getId();

        $format = (string) ($this->nameConventionHelper->getFormat()['format'] ?? 'first_name last_name');
        $items = array_map(
            fn (array $member): array => [
                'id' => $member['id'],
                'name' => $this->formatName($format, $member['firstname'], $member['lastname']),
                'username' => $member['username'],
            ],
            $this->usergroupRepository->findMembersPage($groupId, ($page - 1) * $limit, $limit)
        );

        return $this->json([
            'group' => ['id' => $groupId, 'title' => $usergroup->getTitle()],
            'items' => $items,
            'totalItems' => $this->usergroupRepository->countMembersByUsergroups([$groupId])[$groupId] ?? 0,
            'page' => $page,
            'limit' => $limit,
        ]);
    }

    private function canSeeMembers(Usergroup $usergroup, Request $request): bool
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return true;
        }

        if ($this->isGranted('ROLE_SESSION_MANAGER')
            && 'true' !== $this->settingsManager->getSetting('session.limit_session_admin_role', true)
        ) {
            return true;
        }

        $courseId = $request->query->getInt('cid');
        $course = $courseId > 0 ? $this->em->find(Course::class, $courseId) : null;
        if (!$course instanceof Course) {
            return false;
        }

        $sessionId = $request->query->getInt('sid');
        $session = $sessionId > 0 ? $this->em->find(Session::class, $sessionId) : null;
        if ($sessionId > 0 && (!$session instanceof Session || !$session->hasCourse($course))) {
            return false;
        }

        return $this->isLinkedTo($usergroup, $course, $session)
            && $this->isAllowedToEditHelper->check(false, false, false, false, $course, $session);
    }

    /**
     * The classes the course class list treats as registered (UserGroupModel::usergroup_was_added_in_course()):
     * linked to the session inside a session, to the course otherwise.
     */
    private function isLinkedTo(Usergroup $usergroup, Course $course, ?Session $session): bool
    {
        if (null !== $session) {
            return null !== $this->em->getRepository(UsergroupRelSession::class)->findOneBy(['usergroup' => $usergroup, 'session' => $session]);
        }

        return null !== $this->em->getRepository(UsergroupRelCourse::class)->findOneBy(['usergroup' => $usergroup, 'course' => $course]);
    }

    private function belongsToCurrentUrl(Usergroup $usergroup): bool
    {
        if (!$this->accessUrlHelper->isMultiple()) {
            return true;
        }

        $accessUrl = $this->accessUrlHelper->getCurrent();
        if (null === $accessUrl) {
            return true;
        }

        foreach ($usergroup->getUrls() as $urlRel) {
            if ($urlRel->getUrl()?->getId() === $accessUrl->getId()) {
                return true;
            }
        }

        return false;
    }

    private function formatName(string $format, string $firstname, string $lastname): string
    {
        $name = str_replace(['title ', 'first_name', 'last_name'], ['', $firstname, $lastname], $format);
        $name = (string) preg_replace(['/\s+/u', '/\s*,[\s,]*/u'], [' ', ', '], $name);

        return (string) preg_replace('/^[\s,]+|[\s,]+$/u', '', $name);
    }
}
