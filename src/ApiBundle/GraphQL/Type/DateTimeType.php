<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\ApiBundle\GraphQL\Type;

use GraphQL\Language\AST\Node;

/**
 * Class DateTimeType.
 *
 * @package Chamilo\ApiBundle\GraphQL\Type
 */
class DateTimeType
{
    /**
     * @param \DateTime $value
     *
     * @return string
     */
    public static function serialize(\DateTime $value)
    {
        return $value->format(\DateTime::ATOM);
    }

    /**
     * @param string $value
     *
     * @return \DateTime
     */
    public static function parseValue(string $value)
    {
        return new \DateTime($value, new \DateTimeZone('UTC'));
    }

    /**
     * @param Node $valueNode
     *
     * @return \DateTime
     */
    public static function parseLiteral(Node $valueNode)
    {
        return new \DateTime($valueNode->value, new \DateTimeZone('UTC'));
    }
}
