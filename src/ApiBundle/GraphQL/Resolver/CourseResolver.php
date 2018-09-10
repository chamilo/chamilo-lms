<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\ApiBundle\GraphQL\Resolver;

use Chamilo\ApiBundle\GraphQL\ApiGraphQLTrait;
use Chamilo\CoreBundle\Entity\Course;
use Overblog\GraphQLBundle\Definition\Resolver\AliasedInterface;
use Overblog\GraphQLBundle\Definition\Resolver\ResolverInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

/**
 * Class CourseResolver.
 *
 * @package Chamilo\ApiBundle\GraphQL\Resolver
 */
class CourseResolver implements ResolverInterface, AliasedInterface, ContainerAwareInterface
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
    public static function getAliases()
    {
        return [
            'resolvePicture' => 'course_picture',
            'resolveTeachers' => 'course_teachers',
            'resolveTools' => 'course_tools',
        ];
    }

    /**
     * @param Course $course
     * @param bool   $fullSize
     *
     * @return null|string
     */
    public function resolvePicture(Course $course, $fullSize = false)
    {
        return \CourseManager::getPicturePath($course, $fullSize);
    }

    /**
     * @param Course $course
     *
     * @return array
     */
    public function resolveTeachers(Course $course)
    {
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
