<?php

namespace FranMoreno\Silex\Service;

use Pagerfanta\Pagerfanta;
use Pagerfanta\Adapter\AdapterInterface;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Adapter\DoctrineORMAdapter;

class PagerfantaFactory
{
    public function getForArray($array)
    {
        $adapter = new ArrayAdapter($array);

        return $this->createPagerfanta($adapter);
    }

    public function getForDoctrineORM($query, $fetchJoinCollection = true)
    {
        $adapter = new DoctrineORMAdapter($query, $fetchJoinCollection);

        return $this->createPagerfanta($adapter);
    }

    protected function createPagerfanta(AdapterInterface $adapter)
    {
        return new Pagerfanta($adapter);
    }
}