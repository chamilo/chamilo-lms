<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\ApiBundle\GraphQL\Resolver;

use Chamilo\ApiBundle\GraphQL\ApiGraphQLTrait;
use Chamilo\CoreBundle\Entity\SessionCategory;
use Overblog\GraphQLBundle\Definition\Resolver\AliasedInterface;
use Overblog\GraphQLBundle\Definition\Resolver\ResolverInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

/**
 * Class SessionCategoryResolver
 *
 * @package Chamilo\ApiBundle\GraphQL\Resolver
 */
class SessionCategoryResolver implements ResolverInterface, AliasedInterface, ContainerAwareInterface
{
    use ApiGraphQLTrait;

    /**
     * Returns methods aliases.
     *
     * For instance:
     * array('myMethod' => 'myAlias')
     *
     * @return array
     */
    public static function getAliases(): array
    {
        return [
            'resolveStartDate' => 'sessioncategory_startdate',
            'resolveEndDate' => 'sessioncategory_enddate',
        ];
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
