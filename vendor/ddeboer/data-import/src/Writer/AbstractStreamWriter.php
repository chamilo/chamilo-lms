<?php

namespace Ddeboer\DataImport\Writer;

use Ddeboer\DataImport\Writer;

/**
 * Base class to write into streams
 *
 * @author Benoît Burnichon <bburnichon@gmail.com>
 */
abstract class AbstractStreamWriter implements Writer
{
    use WriterTemplate;

    /**
     * @var resource
     */
    private $stream;

    /**
     * @var boolean
     */
    private $closeStreamOnFinish = true;

    /**
     * @param resource $stream
     */
    public function __construct($stream = null)
    {
        if (null !== $stream) {
            $this->setStream($stream);
        }
    }

    /**
     * Set the stream resource
     *
     * @param resource $stream
     *
     * @throws \InvalidArgumentException
     *
     * @return $this
     */
    public function setStream($stream)
    {
        if (! is_resource($stream) || ! 'stream' == get_resource_type($stream)) {
            throw new \InvalidArgumentException(sprintf(
                'Expects argument to be a stream resource, got %s',
                is_resource($stream) ? get_resource_type($stream) : gettype($stream)
            ));
        }

        $this->stream = $stream;

        return $this;
    }

    /**
     * @return resource
     */
    public function getStream()
    {
        if (null === $this->stream) {
            $this->setStream(fopen('php://temp', 'rb+'));
            $this->setCloseStreamOnFinish(false);
        }

        return $this->stream;
    }

    /**
     * {@inheritdoc}
     */
    public function finish()
    {
        if (is_resource($this->stream) && $this->getCloseStreamOnFinish()) {
            fclose($this->stream);
        }
    }

    /**
     * Should underlying stream be closed on finish?
     *
     * @param boolean $closeStreamOnFinish
     *
     * @return boolean
     */
    public function setCloseStreamOnFinish($closeStreamOnFinish = true)
    {
        $this->closeStreamOnFinish = (bool) $closeStreamOnFinish;

        return $this;
    }

    /**
     * Is Stream closed on finish?
     *
     * @return boolean
     */
    public function getCloseStreamOnFinish()
    {
        return $this->closeStreamOnFinish;
    }
}
