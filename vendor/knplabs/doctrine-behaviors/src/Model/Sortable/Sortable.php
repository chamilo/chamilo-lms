<?php

namespace Knp\DoctrineBehaviors\Model\Sortable;

trait Sortable
{
    protected $sort = 1;

    private $reordered = false;

    /**
     * Get sort.
     *
     * @return sort.
     */
    public function getSort()
    {
        return $this->sort;
    }

    /**
     * Set sort.
     *
     * @param sort the value to set.
     *
     * @return $this
     */
    public function setSort($sort)
    {
        $this->reordered = $this->sort !== $sort;
        $this->sort      = $sort;

        return $this;
    }

    public function isReordered()
    {
        return $this->reordered;
    }

    /**
     * @return $this
     */
    public function setReordered()
    {
        $this->reordered = true;

        return $this;
    }
}
