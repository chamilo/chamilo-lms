<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\ApiBundle\GraphQL\Resolver;

use Chamilo\ApiBundle\GraphQL\ApiGraphQLTrait;
use Chamilo\CourseBundle\Entity\CCourseDescription;
use GraphQL\Type\Definition\ResolveInfo;
use Overblog\GraphQLBundle\Definition\Argument;
use Overblog\GraphQLBundle\Definition\Resolver\ResolverInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

/**
 * Class CourseDescriptionResolver.
 *
 * @package Chamilo\ApiBundle\GraphQL\Resolver
 */
class CourseDescriptionResolver implements ResolverInterface, ContainerAwareInterface
{
    use ApiGraphQLTrait;

    /**
     * @param CCourseDescription $description
     * @param Argument           $args
     * @param ResolveInfo        $info
     * @param \ArrayObject       $context
     */
    public function __invoke(CCourseDescription $description, Argument $args, ResolveInfo $info, \ArrayObject $context)
    {
        $method = 'resolve'.ucfirst($info->fieldName);

        if (method_exists($this, $method)) {
            return $this->$method($description, $args, $context);
        }

        $method = 'get'.ucfirst($info->fieldName);

        if (method_exists($description, $method)) {
            return $description->$method();
        }

        return null;
    }

    /**
     * @param CCourseDescription $description
     *
     * @return int
     */
    public function resolveType(CCourseDescription $description)
    {
        return $description->getDescriptionType();
    }
}
