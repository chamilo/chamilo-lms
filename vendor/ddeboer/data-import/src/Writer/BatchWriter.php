<?php

namespace Ddeboer\DataImport\Writer;

use Ddeboer\DataImport\Writer;

/**
 * @author Markus Bachmann <markus.bachmann@bachi.biz>
 */
class BatchWriter implements Writer
{
    private $delegate;

    private $size;

    private $queue;

    public function __construct(Writer $delegate, $size = 20)
    {
        $this->delegate = $delegate;
        $this->size = $size;
    }

    public function prepare()
    {
        $this->delegate->prepare();

        $this->queue = new \SplQueue();
        $this->queue->setIteratorMode(\SplDoublyLinkedList::IT_MODE_DELETE);
    }

    public function writeItem(array $item)
    {
        $this->queue->push($item);

        if (count($this->queue) >= $this->size) {
            $this->flush();
        }
    }

    public function finish()
    {
        $this->flush();

        $this->delegate->finish();
    }

    private function flush()
    {
        foreach ($this->queue as $item) {
            $this->delegate->writeItem($item);
        }

        if ($this->delegate instanceof FlushableWriter) {
            $this->delegate->flush();
        }
    }
}
