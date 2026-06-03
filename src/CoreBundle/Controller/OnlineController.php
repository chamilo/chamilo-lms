<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Chamilo\CoreBundle\Security\Authorization\Voter\CourseVoter;
use Doctrine\ORM\EntityManagerInterface;
use SocialManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @author Julio Montoya <gugli100@gmail.com>.
 */
#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[Route('/online')]
class OnlineController extends BaseController
{
    public function __construct(
        private readonly UserHelper $userHelper,
    ) {}

    #[Route(path: '/', name: 'users_online', methods: ['GET'], options: ['expose' => true])]
    public function index(): Response
    {
        // @todo don't use legacy code
        $users = who_is_online(0, MAX_ONLINE_USERS);
        $users = SocialManager::display_user_list($users);

        return $this->render(
            '@ChamiloCore/Online/index.html.twig',
            [
                'whoisonline' => $users,
            ]
        );
    }

    #[Route(path: '/in_course/{code}', name: 'online_users_in_course', methods: ['GET', 'POST'], options: ['expose' => true])]
    public function onlineUsersInCourses(Course $course): Response
    {
        // The {code} route parameter maps to Course::$code via the EntityValueResolver
        // (404 if unknown). Require membership before disclosing who is online in it.
        $this->denyAccessUnlessGranted(CourseVoter::VIEW, $course);

        // @todo don't use legacy code
        $users = who_is_online_in_this_course(
            0,
            MAX_ONLINE_USERS,
            $this->userHelper->getCurrent()?->getId(),
            api_get_setting('time_limit_whosonline'),
            $course->getCode()
        );

        $users = SocialManager::display_user_list($users);

        return $this->render(
            '@ChamiloCore/Online/index.html.twig',
            [
                'whoisonline' => $users,
            ]
        );
    }

    #[Route(path: '/in_sessions', name: 'online_users_in_session', methods: ['GET', 'POST'], options: ['expose' => true])]
    public function onlineUsersInCoursesSession(Request $request, EntityManagerInterface $em, int $id = 0): Response
    {
        // Read the course CODE from a filtered query parameter (never raw $_GET) and
        // require membership before disclosing presence.
        $cidReq = trim((string) $request->query->get('cidReq', ''));
        $course = '' !== $cidReq ? $em->getRepository(Course::class)->findOneBy(['code' => $cidReq]) : null;
        if (!$course instanceof Course) {
            throw $this->createNotFoundException('Course not found');
        }
        $this->denyAccessUnlessGranted(CourseVoter::VIEW, $course);

        $users = who_is_online_in_this_course(
            0,
            MAX_ONLINE_USERS,
            $this->userHelper->getCurrent()?->getId(),
            api_get_setting('time_limit_whosonline'),
            $cidReq
        );

        $users = SocialManager::display_user_list($users);

        return $this->render(
            '@ChamiloCore/Online/index.html.twig',
            [
                'whoisonline' => $users,
            ]
        );
    }
}
