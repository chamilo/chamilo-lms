<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\GraphQlBundle\Map;

use Chamilo\GraphQlBundle\Traits\GraphQLTrait;
use Chamilo\UserBundle\Entity\User;
use Overblog\GraphQLBundle\Resolver\ResolverMap;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

/**
 * Class EnumMap.
 *
 * @package Chamilo\GraphQlBundle\Map
 */
class EnumMap extends ResolverMap implements ContainerAwareInterface
{
    use GraphQLTrait;

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
                'TOOL_NOTEBOOK' => TOOL_NOTEBOOK,
                'TOOL_FORUM' => TOOL_FORUM,
                'TOOL_CALENDAR_EVENT' => TOOL_CALENDAR_EVENT,
                'TOOL_DOCUMENT' => TOOL_DOCUMENT,
                'TOOL_LEARNPATH' => TOOL_LEARNPATH,
            ],
            'CourseVisibility' => [
                'COURSE_VISIBILITY_OPEN_WORLD' => COURSE_VISIBILITY_OPEN_WORLD,
                'COURSE_VISIBILITY_OPEN_PLATFORM' => COURSE_VISIBILITY_OPEN_PLATFORM,
                'COURSE_VISIBILITY_REGISTERED' => COURSE_VISIBILITY_REGISTERED,
                'COURSE_VISIBILITY_CLOSED' => COURSE_VISIBILITY_CLOSED,
                'COURSE_VISIBILITY_HIDDEN' => COURSE_VISIBILITY_HIDDEN,
            ],
        ];
    }
}
