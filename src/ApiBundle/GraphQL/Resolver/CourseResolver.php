<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\ApiBundle\GraphQL\Resolver;

use Chamilo\ApiBundle\GraphQL\ApiGraphQLTrait;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelCourseRelUser;
use Chamilo\CourseBundle\Entity\CAnnouncement;
use Chamilo\CourseBundle\Entity\CItemProperty;
use Chamilo\CourseBundle\Entity\CTool;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Overblog\GraphQLBundle\Definition\Argument;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

/**
 * Class CourseResolver.
 *
 * @package Chamilo\ApiBundle\GraphQL\Resolver
 */
class CourseResolver implements ContainerAwareInterface
{
    use ApiGraphQLTrait;

    /**
     * @param Course   $course
     * @param Argument $args
     *
     * @return null|string
     */
    public function getPicture(Course $course, Argument $args)
    {
        return \CourseManager::getPicturePath($course, $args['fullSize']);
    }

    /**
     * @param Course       $course
     * @param Argument     $args
     * @param \ArrayObject $context
     *
     * @return array
     */
    public function getTeachers(Course $course, Argument $args, \ArrayObject $context): array
    {
        if ($context->offsetExists('session')) {
            /** @var Session $session */
            $session = $context->offsetGet('session');

            if ($session) {
                $coaches = [];
                $coachSubscriptions = $session->getUserCourseSubscriptionsByStatus($course, Session::COACH);

                /** @var SessionRelCourseRelUser $coachSubscription */
                foreach ($coachSubscriptions as $coachSubscription) {
                    $coaches[] = $coachSubscription->getUser();
                }

                return $coaches;
            }
        }

        $courseRepo = $this->em->getRepository('ChamiloCoreBundle:Course');
        $teachers = $courseRepo
            ->getSubscribedTeachers($course)
            ->getQuery()
            ->getResult();

        return $teachers;
    }

    /**
     * @param Course       $course
     * @param Argument     $args
     * @param \ArrayObject $context
     *
     * @return ArrayCollection
     */
    public function getTools(Course $course, Argument $args, \ArrayObject $context): ArrayCollection
    {
        $session = null;

        if ($context->offsetExists('session')) {
            /** @var Session $session */
            $session = $context->offsetGet('session');
        }

        if (empty($args['type'])) {
            return $course->getTools($session);
        }

        $criteria = Criteria::create()
            ->where(
                Criteria::expr()->eq('name', $args['type'])
            );

        return $course->getTools($session)->matching($criteria);
    }

    /**
     * @param CTool        $tool
     * @param \ArrayObject $context
     *
     * @return array
     */
    public function getDescriptions(Ctool $tool, \ArrayObject $context)
    {
        /** @var Session $session */
        $session = $context->offsetGet('session');
        $cd = new \CourseDescription();
        $cd->set_course_id($tool->getCourse()->getId());

        if ($session) {
            $cd->set_session_id($session->getId());
        }

        $descriptions = $cd->get_description_data();

        if (empty($descriptions)) {
            return [];
        }

        $qb = $this->em->createQueryBuilder();
        $qb
            ->select('d')
            ->from('ChamiloCourseBundle:CCourseDescription', 'd')
            ->where(
                $qb->expr()->in('d.id', array_keys($descriptions['descriptions']))
            );

        return $qb->getQuery()->getResult();
    }

    /**
     * @param CTool        $tool
     * @param \ArrayObject $context
     *
     * @return array
     */
    public function getAnnouncements(CTool $tool, \ArrayObject $context): array
    {
        $announcementManager = $this->container->get('chamilo_course.entity.manager.announcement_manager');
        $announcementsInfo = $announcementManager->getAnnouncements(
            $this->getCurrentUser(),
            $tool->getCourse(),
            null,
            $context->offsetGet('session'),
            api_get_course_setting('allow_user_edit_announcement') === 'true',
            api_get_configuration_value('hide_base_course_announcements_in_group') === true
        );

        $announcements = [];

        for ($z = 0; $z < count($announcementsInfo); $z += 2) {
            /** @var CAnnouncement $a */
            $a = $announcementsInfo[$z];
            /** @var CItemProperty $ip */
            $ip = $announcementsInfo[$z + 1];

            $announcement = new \stdClass();
            $announcement->id = $a->getIid();
            $announcement->title = $a->getTitle();
            $announcement->content = $a->getContent();
            $announcement->author = $ip->getInsertUser();
            $announcement->lastUpdateDate = $ip->getLasteditDate();

            $announcements[] = $announcement;
        }

        return $announcements;
    }
}
