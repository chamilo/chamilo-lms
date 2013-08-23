<?php

namespace Pagerfanta\Tests\Adapter;

class Solarium3AdapterTest extends SolariumAdapterTest
{
    protected function getSolariumName()
    {
        return 'Solarium 3';
    }

    protected function getClientClass()
    {
        return 'Solarium\Client';
    }

    protected function getQueryClass()
    {
        return 'Solarium\QueryType\Select\Query\Query';
    }

    protected function getResultClass()
    {
        return 'Solarium\QueryType\Select\Result\Result';
    }
}