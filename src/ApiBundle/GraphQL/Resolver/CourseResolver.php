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
     * @param \ArrayObject $context
     *
     * @return array
     */
    public function getTools(Course $course, Argument $args, \ArrayObject $context)
    {
        $sessionId = 0;

        if ($context->offsetExists('session')) {
            /** @var Session $session */
            $session = $context->offsetGet('session');
            $sessionId = $session->getId();
        }

        $tools = \CourseHome::get_tools_category(TOOL_STUDENT_VIEW, $course->getId(), $sessionId);
        $tools = array_filter($tools, function ($tool) {
            switch ($tool['name']) {
                case TOOL_COURSE_DESCRIPTION:
                case TOOL_ANNOUNCEMENT:
                    return true;
                default:
                    return false;
            }
        });

        if (!empty($args['type'])) {
            $tools = array_filter($tools, function ($tool) use ($args) {
                return $tool['name'] === $args['type'];
            });
        }

        $ids = array_column($tools, 'iid');

        $qb = $this->em->createQueryBuilder();
        $qb
            ->select('t')
            ->from('ChamiloCourseBundle:CTool', 't')
            ->where(
                $qb->expr()->in('t.id', $ids)
            );

        return $qb->getQuery()->getResult();
    }
}
