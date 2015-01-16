<?php
/**
 * Created by PhpStorm.
 * User: dbarreto
 * Date: 19/12/14
 * Time: 09:45 AM
 */

class HookWSRegistration extends HookEvent implements HookWSRegistrationEventInterface
{

    protected function __construct()
    {
        parent::__construct('HookWSRegistration');
    }

    /**
     * @param int $type
     * @return int
     */
    public function notifyWSRegistration($type)
    {
        /** @var \HookWSRegistrationObserverInterface $observer */
        if (!isset($this->eventData['server'])) {
            global $server;
            $this->eventData['server'] = $server;
        }
        $this->eventData['type'] = $type;
        foreach ($this->observers as $observer) {
            $data = $observer->hookWSRegistration($this);
            $this->eventData['server'] = $data['server'];
            if (isset($server)) {
                $server = $this->eventData['server'] ;
            }
        }
        return 1;
    }
}