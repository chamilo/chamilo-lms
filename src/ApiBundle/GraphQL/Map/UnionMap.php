<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\ApiBundle\GraphQL\Map;

use Chamilo\ApiBundle\GraphQL\ApiGraphQLTrait;
use Chamilo\CourseBundle\Entity\CTool;
use Overblog\GraphQLBundle\Resolver\ResolverMap;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

/**
 * Class UnionMap.
 *
 * @package Chamilo\ApiBundle\GraphQL\Map
 */
class UnionMap extends ResolverMap implements ContainerAwareInterface
{
    use ApiGraphQLTrait;

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
                    }
                },
            ],
        ];
    }
}
