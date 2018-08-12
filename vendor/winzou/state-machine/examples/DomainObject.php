<?php

/*
 * This file is part of the StateMachine package.
 *
 * (c) Alexandre Bacco
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class DomainObject
{
    private $stateA = 'checkout';
    private $stateB = 'checkout';

    public function getStateA()
    {
        return $this->stateA;
    }

    public function setStateA($state)
    {
        $this->stateA = $state;
    }

    public function getStateB()
    {
        return $this->stateB;
    }

    public function setStateB($state)
    {
        $this->stateB = $state;
    }

    public function setConfirmedNow()
    {
        var_dump('I (the object) am set confirmed at '.date('Y-m-d').'.');
    }
}
