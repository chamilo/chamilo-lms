<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\ApiBundle\GraphQL\Map;

use Chamilo\ApiBundle\GraphQL\ApiGraphQLTrait;
use GraphQL\Error\Error;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\StringValueNode;
use Overblog\GraphQLBundle\Resolver\ResolverMap;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

/**
 * Class ScalarResolverMap.
 *
 * @package Chamilo\ApiBundle\GraphQL\Map
 */
class ScalarMap extends ResolverMap implements ContainerAwareInterface
{
    use ApiGraphQLTrait;

    /**
     * @return array
     */
    protected function map()
    {
        return [
            'DateTime' => [
                self::SERIALIZE => function (\DateTime $value) {
                    return $value->format(\DateTime::ATOM);
                },
                self::PARSE_VALUE => function (string $value) {
                    return new \DateTime($value, new \DateTimeZone('UTC'));
                },
                self::PARSE_LITERAL => function (Node $valueNode) {
                    if (!$valueNode instanceof StringValueNode) {
                        throw new Error('Query error: Can only parse string, got: '.$valueNode->kind, [$valueNode]);
                    }

                    return new \DateTime($valueNode->value, new \DateTimeZone('UTC'));
                },
            ],
        ];
    }
}
