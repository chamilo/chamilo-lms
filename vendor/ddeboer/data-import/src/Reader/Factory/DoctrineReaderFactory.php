<?php

namespace Ddeboer\DataImport\Reader\Factory;

use Ddeboer\DataImport\Reader\DoctrineReader;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Factory that creates DoctrineReaders
 *
 * @author David de Boer <david@ddeboer.nl>
 */
class DoctrineReaderFactory
{
    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @param ObjectManager $objectManager
     */
    public function __construct(ObjectManager $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @param string $object
     *
     * @return DoctrineReader
     */
    public function getReader($object)
    {
        return new DoctrineReader($this->objectManager, $object);
    }
}
