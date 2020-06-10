<?php
/* For licensing terms, see /license.txt */

/**
 * Class CheckLoginCredentialsHook.
 */
class CheckLoginCredentialsHook extends HookEvent implements CheckLoginCredentialsHookEventInterface
{
    /**
     * CheckLoginCredentialsHook constructor.
     *
     * @throws Exception
     */
    protected function __construct()
    {
        parent::__construct('CheckLoginCredentialsHook');
    }

    /**
     * Call to all observers.
     *
     * @return bool
     */
    public function notifyLoginCredentials()
    {
        /** @var CheckLoginCredentialsHookObserverInterface $observer */
        foreach ($this->observers as $observer) {
            $isChecked = $observer->checkLoginCredentials($this);

            if ($isChecked) {
                return true;
            }
        }

        return false;
    }
}
