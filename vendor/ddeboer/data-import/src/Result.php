<?php

namespace Ddeboer\DataImport;

use Ddeboer\DataImport\Exception\ExceptionInterface;

/**
 * Simple Container for Workflow Results
 *
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class Result
{
    /**
     * Identifier given to the import/export
     *
     * @var string
     */
    protected $name;

    /**
     * @var \DateTime
     */
    protected $startTime;

    /**
     * @var \DateTime
     */
    protected $endTime;

    /**
     * @var \DateInterval
     */
    protected $elapsed;

    /**
     * @var integer
     */
    protected $errorCount = 0;

    /**
     * @var integer
     */
    protected $successCount = 0;

    /**
     * @var integer
     */
    protected $totalProcessedCount = 0;

    /**
     * @var \SplObjectStorage
     */
    protected $exceptions;

    /**
     * @param string            $name
     * @param \DateTime         $startTime
     * @param \DateTime         $endTime
     * @param integer           $totalCount
     * @param \SplObjectStorage $exceptions
     */
    public function __construct($name, \DateTime $startTime, \DateTime $endTime, $totalCount, \SplObjectStorage $exceptions)
    {
        $this->name                = $name;
        $this->startTime           = $startTime;
        $this->endTime             = $endTime;
        $this->elapsed             = $startTime->diff($endTime);
        $this->totalProcessedCount = $totalCount;
        $this->errorCount          = count($exceptions);
        $this->successCount        = $totalCount - $this->errorCount;
        $this->exceptions          = $exceptions;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return \DateTime
     */
    public function getStartTime()
    {
        return $this->startTime;
    }

    /**
     * @return \DateTime
     */
    public function getEndTime()
    {
        return $this->endTime;
    }

    /**
     * @return \DateInterval
     */
    public function getElapsed()
    {
        return $this->elapsed;
    }

    /**
     * @return integer
     */
    public function getErrorCount()
    {
        return $this->errorCount;
    }

    /**
     * @return integer
     */
    public function getSuccessCount()
    {
        return $this->successCount;
    }

    /**
     * @return integer
     */
    public function getTotalProcessedCount()
    {
        return $this->totalProcessedCount;
    }

    /**
     * @return boolean
     */
    public function hasErrors()
    {
        return $this->errorCount > 0;
    }

    /**
     * @return \SplObjectStorage
     */
    public function getExceptions()
    {
        return $this->exceptions;
    }
}
