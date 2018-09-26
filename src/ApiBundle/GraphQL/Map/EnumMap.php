<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\ApiBundle\GraphQL\Map;

use Chamilo\ApiBundle\GraphQL\ApiGraphQLTrait;
use Chamilo\UserBundle\Entity\User;
use Overblog\GraphQLBundle\Resolver\ResolverMap;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

/**
 * Class EnumResolverMap.
 *
 * @package Chamilo\ApiBundle\GraphQL\Map
 */
class EnumMap extends ResolverMap implements ContainerAwareInterface
{
    use ApiGraphQLTrait;

    /**
     * @return array
     */
    protected function map()
    {
        return [
            'UserStatus' => [
                'TEACHER' => User::TEACHER,
                'SESSION_ADMIN' => User::SESSION_ADMIN,
                'DRH' => User::DRH,
                'STUDENT' => User::STUDENT,
            ],
            'ImageSize' => [
                'ICON_SIZE_TINY' => ICON_SIZE_TINY,
                'ICON_SIZE_SMALL' => ICON_SIZE_SMALL,
                'ICON_SIZE_MEDIUM' => ICON_SIZE_MEDIUM,
                'ICON_SIZE_LARGE' => ICON_SIZE_LARGE,
                'ICON_SIZE_BIG' => ICON_SIZE_BIG,
                'ICON_SIZE_HUGE' => ICON_SIZE_HUGE,
            ],
            'CourseToolType' => [
                'TOOL_COURSE_DESCRIPTION' => TOOL_COURSE_DESCRIPTION,
                'TOOL_ANNOUNCEMENT' => TOOL_ANNOUNCEMENT,
            ],
        ];
    }
}
