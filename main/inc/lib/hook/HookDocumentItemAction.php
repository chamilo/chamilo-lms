<?php

/* For licensing terms, see /license.txt */

/**
 * Class HookDocumentItemAction.
 */
class HookDocumentItemAction extends HookEvent implements HookDocumentItemActionEventInterface
{
    /**
     * HookDocumentItemAction constructor.
     *
     * @throws \Exception
     */
    protected function __construct()
    {
        parent::__construct('HookDocumentItemAction');
    }

    /**
     * Update all the observers.
     *
     * @param int $type
     *
     * @return array
     */
    public function notifyDocumentItemAction($type)
    {
        $this->eventData['type'] = $type;

        /** @var \HookDocumentItemActionObserverInterface $observer */
        foreach ($this->observers as $observer) {
            $data = $observer->notifyDocumentItemAction($this);
            $this->setEventData($data);
        }

        return $this->eventData;
    }
}
