<?php

/* For licensing terms, see /license.txt */

class HookDocumentAction extends HookEvent implements HookDocumentActionEventInterface
{
    protected function __construct()
    {
        parent::__construct('HookDocumentAction');
    }

    /**
     * Update all the observers.
     *
     * @param int $type
     *
     * @return array
     */
    public function notifyDocumentAction($type)
    {
        $this->eventData['type'] = $type;

        /** @var HookDocumentActionEventInterface $observer */
        foreach ($this->observers as $observer) {
            $data = $observer->notifyDocumentAction($this);
            $this->setEventData($data);
        }

        return $this->eventData;
    }
}
