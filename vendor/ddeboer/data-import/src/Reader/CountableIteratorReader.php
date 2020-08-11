<?php

namespace Ddeboer\DataImport\Reader;

/**
 * Use a class implementing both \Iterator and \Countable as a reader
 *
 * This class uses count() on iterators implementing \Countable interface
 * and iterator_count in any further cases
 *
 * Be careful! iterator_count iterates through the whole iterator loading every data into the memory (for example from streams)
 * It is not recommended for very big datasets.
 *
 * @author Márk Sági-Kazár <mark.sagikazar@gmail.com>
 */
class CountableIteratorReader extends IteratorReader implements CountableReader
{
    /**
     * {@inheritdoc}
     */
    public function count()
    {
        $iterator = $this->getInnerIterator();

        if ($iterator instanceof \Countable) {
            return count($iterator);
        }

        return iterator_count($iterator);
    }
}
