<?php

namespace Ddeboer\DataImport\Writer;

use Ddeboer\DataImport\Reader\CountableReaderInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;

/**
 * Writes output to the Symfony2 console
 *
 */
class ConsoleProgressWriter extends AbstractWriter
{
    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var ProgressBar
     */
    protected $progress;

    /**
     * @var string
     */
    protected $verbosity;

    /**
     * @var CountableReaderInterface
     */
    protected $reader;

    /**
     * @var int
     */
    protected $redrawFrequency;

    /**
     * @param OutputInterface $output
     * @param CountableReaderInterface $reader
     * @param string $verbosity
     * @param int $redrawFrequency
     */
    public function __construct(
        OutputInterface $output,
        CountableReaderInterface $reader,
        $verbosity = 'debug',
        $redrawFrequency = 1
    ) {
        $this->output           = $output;
        $this->reader           = $reader;
        $this->verbosity        = $verbosity;
        $this->redrawFrequency  = $redrawFrequency;
    }

    /**
     * @return $this
     */
    public function prepare()
    {
        $this->progress = new ProgressBar($this->output, $this->reader->count());
        $this->progress->setFormat($this->verbosity);
        $this->progress->setRedrawFrequency($this->redrawFrequency);
        $this->progress->start();

        return $this;
    }

    /**
     * @param array $item
     * @return $this
     */
    public function writeItem(array $item)
    {
        $this->progress->advance();

        return $this;
    }

    /**
     * @return $this
     */
    public function finish()
    {
        $this->progress->finish();

        return $this;
    }

    /**
     * @return string
     */
    public function getVerbosity()
    {
        return $this->verbosity;
    }

    /**
     * @return int
     */
    public function getRedrawFrequency()
    {
        return $this->redrawFrequency;
    }
}
