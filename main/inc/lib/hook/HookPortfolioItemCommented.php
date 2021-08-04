<?php

/* For licensing terms, see /license.txt */

class HookPortfolioItemCommented extends HookEvent implements HookPortfolioItemCommentedEventInterface
{
    /**
     * HookPortfolioItemCommented constructor.
     *
     * @throws \Exception
     */
    protected function __construct()
    {
        parent::__construct('HookPortfolioItemCommented');
    }

    /**
     * {@inheritDoc}
     */
    public function notifyItemCommented()
    {
        /** @var \HookPortfolioItemCommentedObserverInterface $observer */
        foreach ($this->observers as $observer) {
            $observer->hookItemCommented($this);
        }
    }
}
