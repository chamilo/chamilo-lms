<?php

namespace Ddeboer\DataImport\ValueConverter;

use Doctrine\Common\Persistence\ObjectRepository;

/**
 * Converts a string to an object
 *
 * @author Markus Bachmann <markus.bachmann@bachi.biz>
 */
class StringToObjectConverter implements ValueConverterInterface
{
    /**
     * @var ObjectRepository
     */
    private $repository;

    /**
     * @var string
     */
    private $property;

    public function __construct(ObjectRepository $repository, $property)
    {
        $this->repository = $repository;
        $this->property = $property;
    }

    /**
     * {@inheritDoc}
     */
    public function convert($input)
    {
        $method = 'findOneBy'.ucfirst($this->property);

        return $this->repository->$method($input);
    }
}
