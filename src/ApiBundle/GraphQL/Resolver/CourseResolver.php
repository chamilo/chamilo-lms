<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\ApiBundle\GraphQL\Resolver;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Framework\Container;
use Overblog\GraphQLBundle\Definition\Resolver\AliasedInterface;
use Overblog\GraphQLBundle\Definition\Resolver\ResolverInterface;

/**
 * Class CourseResolver.
 *
 * @package Chamilo\ApiBundle\GraphQL\Resolver
 */
class CourseResolver implements ResolverInterface, AliasedInterface
{
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
        $courseRepo = Container::getEntityManager()->getRepository('ChamiloCoreBundle:Course');
        $teachers = $courseRepo
            ->getSubscribedTeachers($course)
            ->getQuery()
            ->getResult();

        return $teachers;
    }
}
