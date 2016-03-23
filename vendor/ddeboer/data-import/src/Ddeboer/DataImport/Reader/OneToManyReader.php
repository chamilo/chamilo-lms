<?php

namespace Ddeboer\DataImport\Reader;

use Ddeboer\DataImport\Exception\ReaderException;

/**
 * Takes multiple readers for processing in the same workflow
 *
 * @author Adam Paterson <hello@adampaterson.co.uk>
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class OneToManyReader implements CountableReaderInterface
{
    /**
     * @var ReaderInterface
     */
    protected $leftReader;

    /**
     * @var ReaderInterface
     */
    protected $rightReader;

    /**
     * @var string Left Join Field
     */
    protected $leftJoinField;

    /**
     * @var string Right Join Field
     */
    protected $rightJoinField;

    /**
     * @var string Key to nest the rightRows under
     */
    protected $nestKey;

    /**
     * @param ReaderInterface $leftReader
     * @param ReaderInterface $rightReader
     * @param string $nestKey
     * @param string $leftJoinField
     * @param string $rightJoinField
     */
    public function __construct(
        ReaderInterface $leftReader,
        ReaderInterface $rightReader,
        $nestKey,
        $leftJoinField,
        $rightJoinField = null
    ) {
        $this->leftJoinField = $leftJoinField;

        if (!$rightJoinField) {
            $this->rightJoinField = $this->leftJoinField;
        } else {
            $this->rightJoinField = $rightJoinField;
        }

        $this->leftReader   = $leftReader;
        $this->rightReader  = $rightReader;
        $this->nestKey      = $nestKey;
    }

    /**
     * Create an array of children in the leftRow,
     * with the data returned from the right reader
     * Where the ID fields Match
     *
     * @return array
     * @throws ReaderException
     */
    public function current()
    {
        $leftRow = $this->leftReader->current();

        if (array_key_exists($this->nestKey, $leftRow)) {
            throw new ReaderException(
                sprintf(
                    'Left Row: "%s" Reader already contains a field named "%s". Please choose a different nest key field',
                    $this->key(),
                    $this->nestKey
                )
            );
        }
        $leftRow[$this->nestKey] = array();

        $leftId     = $this->getRowId($leftRow, $this->leftJoinField);
        $rightRow   = $this->rightReader->current();
        $rightId    = $this->getRowId($rightRow, $this->rightJoinField);

        while ($leftId == $rightId && $this->rightReader->valid()) {

            $leftRow[$this->nestKey][] = $rightRow;
            $this->rightReader->next();

            $rightRow = $this->rightReader->current();

            if($this->rightReader->valid()) {
                $rightId = $this->getRowId($rightRow, $this->rightJoinField);
            }
        }

        return $leftRow;
    }

    /**
     * @param array $row
     * @param string $idField
     * @return mixed
     * @throws ReaderException
     */
    protected function getRowId(array $row, $idField)
    {
        if (!array_key_exists($idField, $row)) {
            throw new ReaderException(
                sprintf(
                    'Row: "%s" has no field named "%s"',
                    $this->key(),
                    $idField
                )
            );
        }

        return $row[$idField];
    }

    /**
     * @return void Any returned value is ignored.
     */
    public function next()
    {
        $this->leftReader->next();
        //right reader is iterated in current() method.
    }

    /**
     * @return mixed scalar on success, or null on failure.
     */
    public function key()
    {
        return $this->leftReader->key();
    }

    /**
     * Checks if current position is valid
     * Returns true on success or false on failure.
     */
    public function valid()
    {
        return $this->leftReader->valid() && $this->rightReader->valid();
    }

    /**
     * Rewind the Iterator to the first element
     * @return void Any returned value is ignored.
     */
    public function rewind()
    {
        $this->leftReader->rewind();
        $this->rightReader->rewind();
    }

    /**
     * @return array
     */
    public function getFields()
    {
        return array_merge($this->leftReader->getFields(), array($this->nestKey));
    }

    /**
     * Count elements of an object
     * The return value is cast to an integer.
     */
    public function count()
    {
        return $this->leftReader->count();
    }
}