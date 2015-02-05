<?php
/**
 * Created by PhpStorm.
 * User: dbarreto
 * Date: 19/12/14
 * Time: 09:45 AM
 */

class HookAdminBlock extends HookEvent implements HookAdminBlockEventInterface {

    protected function __construct()
    {
        parent::__construct('HookAdminBlock');
    }

    /**
     * @param int $type
     * @return int
     */
    public function notifyAdminBlock($type)
    {
        /** @var \HookAdminBlockObserverInterface $observer */
        global $blocks;
        $this->eventData['blocks'] = $blocks;
        $this->eventData['type'] = $type;
        foreach ($this->observers as $observer) {
            $data = $observer->hookAdminBlock($this);
            $blocks = $data['blocks'];
        }
        return 1;
    }
}