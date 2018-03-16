<?php
/* For licensing terms, see /license.txt */

/**
 * This file contains a Hook Event class for Admin Block.
 *
 * @package chamilo.library.hook
 */

/**
 * Class HookWSRegistration
 * This class is a Hook event implementing Webservice Registration Event interface.
 * This class is used to modify ws for registration by notifying Hook Observer
 * for Webservice registration.
 */
class HookWSRegistration extends HookEvent implements HookWSRegistrationEventInterface
{
    /**
     * Construct.
     */
    protected function __construct()
    {
        parent::__construct('HookWSRegistration');
    }

    /**
     * Notify all Hook observer for WS Registration.
     * This save "server" (soap server) and send to Hook observer to be modified
     * (e.g. add more registration webservice).
     *
     * @param int $type Set the type of hook event called.
     *                  0: HOOK_EVENT_TYPE_PRE, 1: HOOK_EVENT_TYPE_POST
     *
     * @return int
     */
    public function notifyWSRegistration($type)
    {
        /** @var \HookWSRegistrationObserverInterface $observer */
        // check if already have server data
        if (isset($this->eventData['server'])) {
            // Save Hook event type data
            $this->eventData['type'] = $type;
            foreach ($this->observers as $observer) {
                // Notify all registered observers
                $data = $observer->hookWSRegistration($this);
                // check if server is not null
                if (isset($data['server'])) {
                    // Get modified server
                    $this->eventData['server'] = $data['server'];
                }
            }

            return $this->eventData;
        }

        return 1;
    }
}
