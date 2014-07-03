<?php

namespace Knp\Component\Pager\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Specific Event class for paginator
 */
class ItemsEvent extends Event
{
    /**
     * A target being paginated
     *
     * @var mixed
     */
    public $target;

    /**
     * List of options
     *
     * @var array
     */
    public $options;

    /**
     * Items result
     *
     * @var mixed
     */
    public $items;

    /**
     * Count result
     *
     * @var integer
     */
    public $count;

    private $offset;
    private $limit;
    private $customPaginationParams = array();

    public function __construct($offset, $limit)
    {
        $this->offset = $offset;
        $this->limit = $limit;
    }

    public function setCustomPaginationParameter($name, $value)
    {
        $this->customPaginationParams[$name] = $value;
    }

    public function getCustomPaginationParameters()
    {
        return $this->customPaginationParams;
    }

    public function getLimit()
    {
        return $this->limit;
    }

    public function getOffset()
    {
        return $this->offset;
    }
}
