<?php

/* For licensing terms, see /license.txt */

class HookDocumentItemAction extends HookEvent implements HookDocumentItemActionEventInterface
{
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

        /** @var HookDocumentItemActionEventInterface $observer */
        foreach ($this->observers as $observer) {
            $data = $observer->notifyDocumentItemAction($this);
            $this->setEventData($data);
        }

        return $this->eventData;
    }
}
