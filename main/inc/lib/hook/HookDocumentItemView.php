<?php

/* For licensing terms, see /license.txt */

/**
 * Class HookDocumentItemView.
 */
class HookDocumentItemView extends HookEvent implements HookDocumentItemViewEventInterface
{
    /**
     * HookDocumentItemView constructor.
     *
     * @throws Exception
     */
    protected function __construct()
    {
        parent::__construct('HookDocumentItemView');
    }

    /**
     * {@inheritDoc}
     */
    public function notifyDocumentItemView(): array
    {
        $tools = [];

        /** @var HookDocumentItemViewObserverInterface $observer */
        foreach ($this->observers as $observer) {
            $tools[] = $observer->notifyDocumentItemView($this);
        }

        return $tools;
    }
}
