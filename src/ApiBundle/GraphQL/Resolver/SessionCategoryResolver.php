<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\ApiBundle\GraphQL\Resolver;

use Chamilo\ApiBundle\GraphQL\ApiGraphQLTrait;
use Chamilo\CoreBundle\Entity\SessionCategory;
use GraphQL\Type\Definition\ResolveInfo;
use Overblog\GraphQLBundle\Definition\Argument;
use Overblog\GraphQLBundle\Definition\Resolver\ResolverInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

/**
 * Class SessionCategoryResolver.
 *
 * @package Chamilo\ApiBundle\GraphQL\Resolver
 */
class SessionCategoryResolver implements ResolverInterface, ContainerAwareInterface
{
    use ApiGraphQLTrait;

    /**
     * @param SessionCategory $sessionCategory
     * @param Argument        $args
     * @param ResolveInfo     $info
     * @param \ArrayObject    $context
     */
    public function __invoke(SessionCategory $sessionCategory, Argument $args, ResolveInfo $info, \ArrayObject $context)
    {
        $method = 'resolve'.ucfirst($info->fieldName);

        if (method_exists($this, $method)) {
            return $this->$method($sessionCategory, $args, $context);
        }

        $method = 'get'.ucfirst($info->fieldName);

        if (method_exists($sessionCategory, $method)) {
            return $sessionCategory->$method();
        }

        return null;
    }

    /**
     * @param SessionCategory $category
     *
     * @return \DateTime
     */
    public function resolveStartDate(SessionCategory $category): \DateTime
    {
        return $category->getDateStart();
    }

    /**
     * @param SessionCategory $category
     *
     * @return \DateTime
     */
    public function resolveEndDate(SessionCategory $category): \DateTime
    {
        return $category->getDateEnd();
    }
}
