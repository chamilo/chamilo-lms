<?php

namespace Ddeboer\DataImport\Writer;

use Ddeboer\DataImport\Reader\CountableReader;
use Ddeboer\DataImport\Writer;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;

/**
 * Writes output to the Symfony2 console
 *
 * @author David de Boer <david@ddeboer.nl>
 */
class ConsoleProgressWriter implements Writer
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
     * @var CountableReader
     */
    protected $reader;

    /**
     * @var integer
     */
    protected $redrawFrequency;

    /**
     * @param OutputInterface $output
     * @param CountableReader $reader
     * @param string          $verbosity
     * @param integer         $redrawFrequency
     */
    public function __construct(
        OutputInterface $output,
        CountableReader $reader,
        $verbosity = 'debug',
        $redrawFrequency = 1
    ) {
        $this->output           = $output;
        $this->reader           = $reader;
        $this->verbosity        = $verbosity;
        $this->redrawFrequency  = $redrawFrequency;
    }

    /**
     * {@inheritdoc}
     */
    public function prepare()
    {
        $this->progress = new ProgressBar($this->output, $this->reader->count());
        $this->progress->setFormat($this->verbosity);
        $this->progress->setRedrawFrequency($this->redrawFrequency);
        $this->progress->start();
    }

    /**
     * {@inheritdoc}
     */
    public function writeItem(array $item)
    {
        $this->progress->advance();
    }

    /**
     * {@inheritdoc}
     */
    public function finish()
    {
        $this->progress->finish();
    }

    /**
     * @return string
     */
    public function getVerbosity()
    {
        return $this->verbosity;
    }

    /**
     * @return integer
     */
    public function getRedrawFrequency()
    {
        return $this->redrawFrequency;
    }
}
