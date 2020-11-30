<?php

/* For licensing terms, see /license.txt */

/**
 * Class HookDocumentAction.
 */
class HookDocumentAction extends HookEvent implements HookDocumentActionEventInterface
{
    /**
     * HookDocumentAction constructor.
     *
     * @throws \Exception
     */
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

        /** @var \HookDocumentActionObserverInterface $observer */
        foreach ($this->observers as $observer) {
            $data = $observer->notifyDocumentAction($this);
            $this->setEventData($data);
        }

        return $this->eventData;
    }
}
