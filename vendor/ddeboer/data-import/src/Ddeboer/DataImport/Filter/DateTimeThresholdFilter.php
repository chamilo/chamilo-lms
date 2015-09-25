<?php

namespace Ddeboer\DataImport\Filter;

use Ddeboer\DataImport\ValueConverter\DateTimeValueConverter;

/**
 * This filter can be used to filter out some items from a specific date. Useful
 * to do incremental imports
 */
class DateTimeThresholdFilter implements FilterInterface
{
    /**
     * @var DateTime threshold dates strictly before this date will be filtered out.
     *               defaults to null
     */
    protected $threshold;

    /**
     * @var DateTimeValueConverter used to convert the values in the time column
     */
    protected $valueConverter;

    /**
     * @var string the name of the column that should contain the value the
     *             filter will compare the threshold with. Defaults to "updated_at"
     */
    protected $timeColumnName;

    /**
     * @var int priority the filter priority. Defaults to 512.
     */
    protected $priority;

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
     * {@inheritDoc}
     */
    public function filter(array $item)
    {
        if ($this->threshold == null) {
            throw new \LogicException('Make sure you set a threshold');
        }

        return
            $this->valueConverter->convert($item[$this->timeColumnName])
            >=
            $this->threshold;
    }

    /**
     * Useful if you build a filter service, and want to set the threshold
     * dynamically afterwards.
     */
    public function setThreshold(\DateTime $value)
    {
        $this->threshold = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority()
    {
        return $this->priority;
    }
}
