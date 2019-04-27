<?php

namespace Knp\DoctrineBehaviors\ORM\Geocodable\Query\AST\Functions;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\AST\PathExpression;

/**
 * DQL function for calculating distances in meters between two points
 *
 * DISTANCE(entity.point, :latitude, :longitude)
 */
class DistanceFunction extends FunctionNode
{
    private $entityLocation;
    private $latitude;
    private $longitude;

    /**
     * Returns SQL representation of this function.
     *
     * @param SqlWalker $sqlWalker
     *
     * @return string
     */
    public function getSql(SqlWalker $sqlWalker)
    {
        $entityLocation = $this->entityLocation->dispatch($sqlWalker);
        return sprintf('earth_distance(ll_to_earth(%s[0], %s[1]),ll_to_earth(%s, %s))',
            $entityLocation,
            $entityLocation,
            $this->latitude->dispatch($sqlWalker),
            $this->longitude->dispatch($sqlWalker)
        );
    }

    /**
     * Parses DQL function.
     *
     * @param Parser $parser
     */
    public function parse(Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);
        $this->entityLocation = $parser->ArithmeticPrimary();
        $parser->match(Lexer::T_COMMA);
        $this->latitude = $parser->ArithmeticPrimary();
        $parser->match(Lexer::T_COMMA);
        $this->longitude = $parser->ArithmeticPrimary();
        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }
}
