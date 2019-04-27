<?php

/*
* This file is part of the StateMachine package.
*
* (c) Alexandre Bacco
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace SM\Factory;

interface ClearableFactoryInterface extends FactoryInterface
{
    /**
     * Clears all state machines from the factory
     */
    public function clear();
}
