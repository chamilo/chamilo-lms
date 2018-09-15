<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\ApiBundle\GraphQL\Resolver;

use Chamilo\CourseBundle\Entity\CTool;
use GraphQL\Type\Definition\ResolveInfo;
use Overblog\GraphQLBundle\Definition\Argument;

/**
 * Trait CourseToolResolverTrait
 *
 * @package Chamilo\ApiBundle\GraphQL\Resolver
 */
trait CourseToolResolverTrait
{
    /**
     * @param CTool        $tool
     * @param Argument     $args
     * @param ResolveInfo  $info
     * @param \ArrayObject $context
     *
     * @return mixed
     */
    public function __invoke(CTool $tool, Argument $args, ResolveInfo $info, \ArrayObject $context)
    {
        $method = 'resolve'.ucfirst($info->fieldName);

        if (method_exists($this, $method)) {
            return $this->$method($tool, $args, $context);
        }

        $method = 'get'.ucfirst($info->fieldName);

        if (method_exists($tool, $method)) {
            return $tool->$method();
        }

        return null;
    }

    /**
     * @param CTool $tool
     *
     * @return bool
     */
    public function resolveIsVisible(CTool $tool): bool
    {
        return (bool) $tool->getVisibility();
    }
}
