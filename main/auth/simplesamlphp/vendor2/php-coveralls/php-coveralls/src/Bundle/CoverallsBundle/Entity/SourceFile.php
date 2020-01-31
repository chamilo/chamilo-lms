<?php

namespace PhpCoveralls\Bundle\CoverallsBundle\Entity;

/**
 * Data represents "source_files" element of Coveralls' "json_file".
 *
 * @author Kitamura Satoshi <with.no.parachute@gmail.com>
 */
class SourceFile extends Coveralls
{
    /**
     * Source filename.
     *
     * @var string
     */
    protected $name;

    /**
     * Source content.
     *
     * @var string
     */
    protected $source;

    /**
     * Coverage data of the source file.
     *
     * @var array
     */
    protected $coverage;

    /**
     * Absolute path.
     *
     * @var string
     */
    protected $path;

    /**
     * Line number of the source file.
     *
     * @var int
     */
    protected $fileLines;

    /**
     * Metrics.
     *
     * @var Metrics
     */
    protected $metrics;

    /**
     * Constructor.
     *
     * @param string $path absolute path
     * @param string $name source filename
     * @param string $eol  end of line
     */
    public function __construct($path, $name, $eol = "\n")
    {
        $this->path = $path;
        $this->name = $name;
        $this->source = trim(file_get_contents($path));

        $lines = explode($eol, $this->source);
        $this->fileLines = count($lines);
        $this->coverage = array_fill(0, $this->fileLines, null);
    }

    /**
     * {@inheritdoc}
     *
     * @see \PhpCoveralls\Bundle\CoverallsBundle\Entity\ArrayConvertable::toArray()
     */
    public function toArray()
    {
        return [
            'name' => $this->name,
            'source' => $this->source,
            'coverage' => $this->coverage,
        ];
    }

    // API

    /**
     * Add coverage.
     *
     * @param int $lineNum line number
     * @param int $count   number of covered
     */
    public function addCoverage($lineNum, $count)
    {
        if (array_key_exists($lineNum, $this->coverage)) {
            $this->coverage[$lineNum] += $count;
        }
    }

    /**
     * Return line coverage.
     *
     * @return float
     */
    public function reportLineCoverage()
    {
        return $this->getMetrics()->getLineCoverage();
    }

    // accessor

    /**
     * Return source filename.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Return source content.
     *
     * @return string
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Return coverage data of the source file.
     *
     * @return array
     */
    public function getCoverage()
    {
        return $this->coverage;
    }

    /**
     * Return absolute path.
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Return line number of the source file.
     *
     * @return int
     */
    public function getFileLines()
    {
        return $this->fileLines;
    }

    /**
     * Return metrics.
     *
     * @return Metrics
     */
    public function getMetrics()
    {
        if ($this->metrics === null) {
            $this->metrics = new Metrics($this->coverage);
        }

        return $this->metrics;
    }
}
