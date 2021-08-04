<?php
/* For licensing terms, see /license.txt */

/**
 * Class HookQuizEnd.
 */
class HookQuizEnd extends HookEvent implements HookQuizEndEventInterface
{
    /**
     * HookQuizEnd constructor.
     *
     * @throws Exception
     */
    protected function __construct()
    {
        parent::__construct('HookQuizEnd');
    }

    /**
     * {@inheritdoc}
     */
    public function notifyQuizEnd()
    {
        /** @var HookQuizEndObserverInterface $observer */
        foreach ($this->observers as $observer) {
            $observer->hookQuizEnd($this);
        }
    }
}
