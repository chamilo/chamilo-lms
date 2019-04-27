<?php

namespace Ddeboer\DataImport\Filter;

use Ddeboer\DataImport\ValueConverter\DateTimeValueConverter;

/**
 * This filter can be used to filter out some items from a specific date.
 *
 * Useful to do incremental imports
 *
 * @author Grégoire Paris
 */
class DateTimeThresholdFilter
{
    /**
     * Threshold dates strictly before this date will be filtered out
     *
     * @var \DateTime|null
     */
    protected $threshold;

    /**
     * Used to convert the values in the time column
     *
     * @var DateTimeValueConverter
     */
    protected $valueConverter;

    /**
     * The name of the column that should contain the value the filter will compare the threshold with
     *
     * @var string
     */
    protected $timeColumnName = 'updated_at';

    /**
     * @var integer
     */
    protected $priority = 512;

    /**
     * @param DateTimeValueConverter $valueConverter
     * @param \DateTime|null         $threshold
     * @param string                 $timeColumnName
     * @param integer                $priority
     */
    public function __construct(
        DateTimeValueConverter $valueConverter,
        \DateTime $threshold = null,
        $timeColumnName = 'updated_at',
        $priority = 512
    ) {
        $this->valueConverter = $valueConverter;
        $this->threshold = $threshold;
        $this->timeColumnName = $timeColumnName;
        $this->priority = $priority;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(array $item)
    {
        if ($this->threshold == null) {
            throw new \LogicException('Make sure you set a threshold');
        }

        $threshold = call_user_func($this->valueConverter, $item[$this->timeColumnName]);

        return $threshold >= $this->threshold;
    }

    /**
     * Useful if you build a filter service, and want to set the threshold
     * dynamically afterwards.
     *
     * @param \DateTime $value
     */
    public function setThreshold(\DateTime $value)
    {
        $this->threshold = $value;
    }
}
