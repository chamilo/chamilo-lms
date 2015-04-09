<?php
/* For licensing terms, see /license.txt */

/**
 * Create Skype user field
 *
 * @author Imanol Losada Oriol <imanol.losada@beeznest.com>
 * @package chamilo.plugin.skype
 */
class HookObserverSkype extends HookObserver implements HookSkypeObserverInterface
{

    /**
     * Class constructor
     */
    public function __construct()
    {
        parent::__construct(
            'plugin/skype/src/Skype.php', 'skype'
        );
    }

    /**
     * Create Skype user field when plugin is enabled
     * @param HookSkypeEventInterface $hook The hook
     */
    public function hookEventSkype(HookSkypeEventInterface $hook)
    {
        $data = $hook->getEventData();
        if ($data['type'] === HOOK_EVENT_TYPE_PRE) {
            // Code
        }
    }
}
