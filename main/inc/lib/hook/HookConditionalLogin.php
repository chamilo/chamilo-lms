<?php
/* For licensing terms, see /license.txt */

/**
 * Class HookConditionalLogin.
 * Hook to implement Conditional Login.
 */
class HookConditionalLogin extends HookEvent implements HookConditionalLoginEventInterface
{
    /**
     * HookConditionalLogin constructor.
     *
     * @throws Exception
     */
    protected function __construct()
    {
        parent::__construct('HookConditionalLogin');
    }

    /**
     * Notify to all hook observers.
     *
     * @return array
     */
    public function notifyConditionalLogin()
    {
        $conditions = [];

        /** @var HookConditionalLoginObserverInterface $observer */
        foreach ($this->observers as $observer) {
            $conditions[] = $observer->hookConditionalLogin($this);
        }

        return $conditions;
    }
}
