<?php

namespace Ddeboer\DataImport\Writer;

/**
 * Base class to write into streams
 *
 * @author Benoît Burnichon <bburnichon@gmail.com>
 */
abstract class AbstractStreamWriter implements WriterInterface
{
    private $stream;
    private $closeStreamOnFinish = true;

    /**
     * Constructor
     *
     * @param resource $stream
     */
    public function __construct($stream = null)
    {
        if (null !== $stream) {
            $this->setStream($stream);
        }
    }

    /**
     * Set Stream Resource
     *
     * @param $stream
     * @throws \InvalidArgumentException
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
     * Get underlying stream resource
     *
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
     * @inheritdoc
     */
    public function prepare()
    {
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function finish()
    {
        if (is_resource($this->stream) && $this->getCloseStreamOnFinish()) {
            fclose($this->stream);
        }

        return $this;
    }

    /**
     * Should underlying stream be closed on finish?
     *
     * @param bool $closeStreamOnFinish
     *
     * @return bool
     */
    public function setCloseStreamOnFinish($closeStreamOnFinish = true)
    {
        $this->closeStreamOnFinish = (bool) $closeStreamOnFinish;

        return $this;
    }

    /**
     * Is Stream closed on finish?
     *
     * @return bool
     */
    public function getCloseStreamOnFinish()
    {
        return $this->closeStreamOnFinish;
    }
}
