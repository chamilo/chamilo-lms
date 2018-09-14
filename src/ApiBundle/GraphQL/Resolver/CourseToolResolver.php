<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\ApiBundle\GraphQL\Resolver;

use Chamilo\ApiBundle\GraphQL\ApiGraphQLTrait;
use Chamilo\CourseBundle\Entity\CTool;
use Overblog\GraphQLBundle\Definition\Resolver\ResolverInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

/**
 * Class CourseToolResolver
 *
 * @package Chamilo\ApiBundle\GraphQL\Resolver
 */
class CourseToolResolver implements ResolverInterface, ContainerAwareInterface
{
    use ApiGraphQLTrait;

    public function __invoke()
    {
        //return null;
        //$method = 'resolve'.ucfirst($info->fieldName);
        //
        //if (method_exists($this, $method)) {
        //    return $this->$method($tool, $context);
        //}
        //
        //$method = 'get'.ucfirst($info->fieldName);
        //
        //if (method_exists($tool, $method)) {
        //    return $tool->$method();
        //}

        //return (new CTool());
    }

    /**
     * @param CTool $tool
     *
     * @return bool
     */
    public function resolveIsVisible($tool)
    {
        return !!$tool->getVisibility();
    }
}
