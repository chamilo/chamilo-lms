<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\GraphQlBundle\Map;

use Chamilo\GraphQlBundle\Traits\GraphQLTrait;
use Chamilo\CourseBundle\Entity\CTool;
use Overblog\GraphQLBundle\Resolver\ResolverMap;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

/**
 * Class UnionMap.
 *
 * @package Chamilo\GraphQlBundle\Map
 */
class UnionMap extends ResolverMap implements ContainerAwareInterface
{
    use GraphQLTrait;

    /**
     * @return array
     */
    protected function map()
    {
        return [
            'CourseTool' => [
                self::RESOLVE_TYPE => function (CTool $tool) {
                    switch ($tool->getName()) {
                        case TOOL_COURSE_DESCRIPTION:
                            return 'ToolDescription';
                        case TOOL_ANNOUNCEMENT:
                            return 'ToolAnnouncements';
                        case TOOL_NOTEBOOK:
                            return 'ToolNotebook';
                        case TOOL_FORUM:
                            return 'ToolForums';
                        case TOOL_CALENDAR_EVENT:
                            return 'ToolAgenda';
                        case TOOL_DOCUMENT:
                            return 'ToolDocuments';
                        case TOOL_LEARNPATH:
                            return 'ToolLearningPath';
                    }
                },
            ],
        ];
    }
}
