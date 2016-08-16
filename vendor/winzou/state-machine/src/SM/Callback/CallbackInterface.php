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

use SM\Event\TransitionEvent;

interface CallbackInterface
{
    /**
     * Calls the callback if its specifications pass the event
     *
     * @param TransitionEvent $event
     *
     * @return mixed
     */
    public function __invoke(TransitionEvent $event);

    /**
     * Determines if the callback specifications pass the event
     *
     * @param TransitionEvent $event
     *
     * @return bool
     */
    public function isSatisfiedBy(TransitionEvent $event);
}
