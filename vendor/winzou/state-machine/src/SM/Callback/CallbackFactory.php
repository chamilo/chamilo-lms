<?php

/*
 * This file is part of the StateMachine package.
 *
 * (c) Alexandre Bacco
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SM\Callback;

use SM\SMException;

class CallbackFactory implements CallbackFactoryInterface
{
    /**
     * @var string
     */
    protected $class;

    public function __construct($class)
    {
        if (!class_exists($class)) {
            throw new SMException(sprintf(
               'Class %s given to CallbackFactory does not exist.',
                $class
            ));
        }

        $this->class = $class;
    }

    /**
     * {@inheritDoc}
     */
    public function get(array $specs)
    {
        if (!isset($specs['do'])) {
            throw new SMException(sprintf(
               'CallbackFactory::get needs the index "do" to be able to build a callback, array %s given.',
                json_encode($specs)
            ));
        }

        $class = $this->class;
        return new $class($specs, $specs['do']);
    }
}
