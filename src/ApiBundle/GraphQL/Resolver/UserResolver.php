<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\ApiBundle\GraphQL\Resolver;

use Chamilo\ApiBundle\GraphQL\ApiGraphQLTrait;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Repository\MessageRepository;
use Chamilo\UserBundle\Entity\User;
use Overblog\GraphQLBundle\Definition\Resolver\AliasedInterface;
use Overblog\GraphQLBundle\Definition\Resolver\ResolverInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

/**
 * Class UserResolver.
 *
 * @package Chamilo\ApiBundle\GraphQL\Resolver
 */
class UserResolver implements ResolverInterface, AliasedInterface, ContainerAwareInterface
{
    use ApiGraphQLTrait;

    /**
     * Returns methods aliases.
     *
     * For instance:
     * array('myMethod' => 'myAlias')
     *
     * @return array
     */
    public static function getAliases(): array
    {
        return [
            'resolveUserPicture' => 'user_picture',
            'resolveEmail' => 'user_email',
            'resolveUserMessages' => 'user_messages',
            'resolveMessageContacts' => 'user_message_contacts',
            'resolveCourses' => 'user_courses',
            'resolveSessions' => 'user_sessions',
        ];
    }

    /**
     * @param User $user
     * @param int  $size
     *
     * @return string
     */
    public function resolveUserPicture(User $user, $size): string
    {
        $assets = $this->container->get('templating.helper.assets');
        $path = $user->getAvatarOrAnonymous((int) $size);

        return $assets->getUrl($path);
    }

    /**
     * @param User         $user
     * @param \ArrayObject $context
     *
     * @return string
     */
    public function resolveEmail(User $user, \ArrayObject $context)
    {
        /** @var User $contextUser */
        $contextUser = $context['user'];

        if ($user->getId() === $contextUser->getId()) {
            return $user->getEmail();
        }

        $settingsManager = $this->container->get('chamilo.settings.manager');
        $showEmail = $settingsManager->getSetting('display.show_email_addresses') === 'true';

        if (!$showEmail) {
            return '';
        }

        return $user->getEmail();
    }

    /**
     * @param User         $user
     * @param int          $lastId
     * @param \ArrayObject $context
     *
     * @return array
     */
    public function resolveUserMessages(User $user, $lastId = 0, \ArrayObject $context): array
    {
        $this->protectUserData($context, $user);

        /** @var MessageRepository $messageRepo */
        $messageRepo = $this->em->getRepository('ChamiloCoreBundle:Message');
        $messages = $messageRepo->getFromLastOneReceived($user, (int) $lastId);

        return $messages;
    }

    /**
     * @param User         $user
     * @param \ArrayObject $context
     *
     * @return array
     */
    public function resolveCourses(User $user, \ArrayObject $context)
    {
        $this->protectUserData($context, $user);

        $courses = [];
        $coursesInfo = \CourseManager::get_courses_list_by_user_id($user->getId());
        $coursesRepo = $this->em->getRepository('ChamiloCoreBundle:Course');

        foreach ($coursesInfo as $courseInfo) {
            /** @var Course $course */
            $course = $coursesRepo->find($courseInfo['real_id']);

            if ($course) {
                $courses[] = $course;
            }
        }

        return $courses;
    }

    /**
     * @param User         $user
     * @param string       $filter
     * @param \ArrayObject $context
     *
     * @return array
     */
    public function resolveMessageContacts(User $user, $filter, \ArrayObject $context): array
    {
        $this->protectUserData($context, $user);

        if (strlen($filter) < 3) {
            return [];
        }

        $usersRepo = $this->em->getRepository('ChamiloUserBundle:User');
        $users = $usersRepo->findUsersToSendMessage($user->getId(), $filter);

        return $users;
    }

    /**
     * @param User $user
     *
     * @todo Based on UserManager::get_sessions_by_category. Review to integrate Symfony
     *
     * @return array
     */
    private function getUserSessions(User $user)
    {
        $allowOrder = api_get_configuration_value('session_list_order');
        $showAllSessions = api_get_configuration_value('show_all_sessions_on_my_course_page') === true;
        $orderBySettings = api_get_configuration_value('my_courses_session_order');

        $position = '';

        if ($allowOrder) {
            $position = ', s.position AS position ';
        }

        $now = api_get_utc_datetime(null, false, true);

        $dql = "SELECT DISTINCT
                    s.id,
                    s.accessEndDate AS access_end_date,
                    s.duration,
                    CASE WHEN s.accessEndDate IS NULL THEN 1 ELSE 0 END HIDDEN _isFieldNull
                    $position
                FROM ChamiloCoreBundle:Session AS s
                LEFT JOIN ChamiloCoreBundle:SessionRelCourseRelUser AS scu WITH scu.session = s
                INNER JOIN ChamiloCoreBundle:AccessUrlRelSession AS url WITH url.session = s.id
                LEFT JOIN ChamiloCoreBundle:SessionCategory AS sc WITH s.category = sc
                WHERE (scu.user = :user OR s.generalCoach = :user) AND url.url = :url";

        $order = "ORDER BY sc.name, s.name";

        if ($showAllSessions) {
            $order = "ORDER BY s.accessStartDate";
        }

        if ($allowOrder) {
            $order = "ORDER BY s.position";
        }

        if (!empty($orderBySettings) && isset($orderBySettings['field']) && isset($orderBySettings['order'])) {
            $field = $orderBySettings['field'];
            $orderSetting = $orderBySettings['order'];

            switch ($field) {
                case 'start_date':
                    $order = "ORDER BY s.accessStartDate $orderSetting";
                    break;
                case 'end_date':
                    $order = " ORDER BY s.accessEndDate $orderSetting ";
                    if ($orderSetting == 'asc') {
                        // Put null values at the end
                        // https://stackoverflow.com/questions/12652034/how-can-i-order-by-null-in-dql
                        $order = "ORDER BY _isFieldNull asc, s.accessEndDate asc";
                    }
                    break;
            }
        }

        $results = [];
        $rows = $this->em
            ->createQuery("$dql $order")
            ->setParameters(
                [
                    'user' => $user->getId(),
                    'url' => api_get_current_access_url_id(),
                ]
            )
            ->getResult();

        foreach ($rows as $row) {
            $coachList = \SessionManager::getCoachesBySession($row['id']);
            $courseList = \UserManager::get_courses_list_by_session(
                $user->getId(),
                $row['id']
            );
            $daysLeft = \SessionManager::getDayLeftInSession(
                ['id' => $row['id'], 'duration' => $row['duration']],
                $user->getId()
            );
            $isGeneralCoach = \SessionManager::user_is_general_coach($user->getId(), $row['id']);
            $isCoachOfCourse = in_array($user->getId(), $coachList);

            if (!$isGeneralCoach && !$isCoachOfCourse) {
                // Teachers can access the session depending in the access_coach date
                if ($row['duration']) {
                    if ($daysLeft <= 0) {
                        continue;
                    }
                } else {
                    if (isset($row['access_end_date']) && !empty($row['access_end_date'])) {
                        if ($row['access_end_date'] <= $now) {
                            continue;
                        }
                    }
                }
            }

            $visibility = api_get_session_visibility($row['id'], null, false);

            if ($visibility != SESSION_VISIBLE) {
                // Course Coach session visibility.
                $blockedCourseCount = 0;
                $closedVisibilityList = [COURSE_VISIBILITY_CLOSED, COURSE_VISIBILITY_HIDDEN];
                $sessionCourseVisibility = SESSION_INVISIBLE;

                foreach ($courseList as $course) {
                    // Checking session visibility
                    $sessionCourseVisibility = api_get_session_visibility(
                        $row['id'],
                        $course['real_id'],
                        false
                    );

                    $courseIsVisible = !in_array($course['visibility'], $closedVisibilityList);

                    if ($courseIsVisible === false || $sessionCourseVisibility == SESSION_INVISIBLE) {
                        $blockedCourseCount++;
                    }
                }

                // If all courses are blocked then no show in the list.
                if ($blockedCourseCount !== count($courseList)) {
                    $visibility = $sessionCourseVisibility;
                }
            }

            if ($visibility == SESSION_INVISIBLE) {
                continue;
            }

            $results[] = $row['id'];
        }

        return $results;
    }

    /**
     * @param User         $user
     * @param \ArrayObject $context
     *
     * @return array
     */
    public function resolveSessions(User $user, \ArrayObject $context): array
    {
        $this->protectUserData($context, $user);

        $sessionsId = $this->getUserSessions($user);

        if (empty($sessionsId)) {
            return [];
        }

        $qb = $this->em->createQueryBuilder();
        $result = $qb
            ->select('s')
            ->from('ChamiloCoreBundle:Session', 's')
            ->where(
                $qb->expr()->in('s.id', $sessionsId)
            )
            ->getQuery()
            ->getResult();

        return $result;
    }
}
