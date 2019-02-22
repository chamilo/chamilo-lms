<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\GraphQlBundle\Map;

use Chamilo\CourseBundle\Entity\CTool;
use Chamilo\GraphQlBundle\Resolver\CourseResolver;
use Chamilo\GraphQlBundle\Traits\GraphQLTrait;
use Overblog\GraphQLBundle\Resolver\ResolverMap;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

/**
 * Class InterfaceMap.
 *
 * @package Chamilo\GraphQlBundle\Map
 */
class InterfaceMap extends ResolverMap implements ContainerAwareInterface
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
                    $toolNames = CourseResolver::getAvailableTools();

                    return $toolNames[$tool->getName()];
                },
            ],
        ];
    }
}
