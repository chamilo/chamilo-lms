<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\ApiBundle\GraphQL\Resolver;

use Chamilo\ApiBundle\GraphQL\ApiGraphQLTrait;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelCourseRelUser;
use GraphQL\Type\Definition\ResolveInfo;
use Overblog\GraphQLBundle\Definition\Argument;
use Overblog\GraphQLBundle\Definition\Resolver\ResolverInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

/**
 * Class CourseResolver.
 *
 * @package Chamilo\ApiBundle\GraphQL\Resolver
 */
class CourseResolver implements ResolverInterface, ContainerAwareInterface
{
    use ApiGraphQLTrait;

    /**
     * @param Course       $course
     * @param Argument     $args
     * @param ResolveInfo  $info
     * @param \ArrayObject $context
     *
     * @return null
     */
    public function __invoke(Course $course, Argument $args, ResolveInfo $info, \ArrayObject $context)
    {
        $context->offsetSet('course', $course);

        $method = 'resolve'.ucfirst($info->fieldName);

        if (method_exists($this, $method)) {
            return $this->$method($course, $args, $context);
        }

        $method = 'get'.ucfirst($info->fieldName);

        if (method_exists($course, $method)) {
            return $course->$method();
        }

        return null;
    }

    /**
     * @param Course   $course
     * @param Argument $args
     *
     * @return null|string
     */
    public function resolvePicture(Course $course, Argument $args)
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
    public function resolveTeachers(Course $course, Argument $args, \ArrayObject $context)
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
     * @param Course $course
     *
     * @return array
     */
    public function resolveTools(Course $course)
    {
        $tools = \CourseHome::get_tools_category(TOOL_STUDENT_VIEW, $course->getId());

        return array_column($tools, 'name');
    }
}
