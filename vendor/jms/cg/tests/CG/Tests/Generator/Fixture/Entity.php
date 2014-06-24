<?php

namespace CG\Tests\Generator\Fixture;

/**
 * Doc Comment.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
abstract class Entity
{
    /**
     * @var integer
     */
    private $id;
    private $enabled = false;

    /**
     * Another doc comment.
     *
     * @param unknown_type $a
     * @param array $b
     * @param \stdClass $c
     * @param unknown_type $d
     */
    public final function __construct($a, array &$b, \stdClass $c, $d = 'foo')
    {
    }

    abstract protected function foo();

    private static function bar()
    {
    }
}