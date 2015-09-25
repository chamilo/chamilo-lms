<?php

namespace Ddeboer\DataImport\Filter;

/**
 * This filter can be used to filter out some items from the beginning and/or
 * end of the items.
 *
 * @author Ville Mattila <ville@eventio.fi>
 */
class OffsetFilter implements FilterInterface
{
    /**
     * @var int
     */
    protected $offset = 0;

    /**
     * @var int|null
     */
    protected $limit = null;

    /**
     * @var int
     */
    protected $offsetCount = 0;

    /**
     * @var int
     */
    protected $sliceCount = 0;

    /**
     * @var boolean
     */
    protected $maxLimitHit = false;

    /**
     * Constructor
     *
     * @param int      $offset 0-based index of the item to start read from
     * @param int|null $limit  Maximum count of items to read. null = no limit
     */
    public function __construct($offset = 0, $limit = null)
    {
        $this->offset = $offset;
        $this->limit = $limit;
    }

    /**
     * {@inheritDoc}
     */
    public function filter(array $item)
    {
        // In case we've already filtered up to limited
        if ($this->maxLimitHit) {
            return false;
        }

        $this->offsetCount++;

        // We have not reached the start offset
        if ($this->offsetCount < $this->offset + 1) {
            return false;
        }

        // There is no maximum limit, so we'll return always true
        if (null === $this->limit) {
            return true;
        }

        $this->sliceCount++;

        if ($this->sliceCount < $this->limit) {
            return true;
        } elseif ($this->sliceCount == $this->limit) {
            $this->maxLimitHit = true;

            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority()
    {
        return 128;
    }
}
