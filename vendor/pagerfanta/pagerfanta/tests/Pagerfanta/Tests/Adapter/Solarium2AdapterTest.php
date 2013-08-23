<?php

namespace Pagerfanta\Tests\Adapter;

class Solarium2AdapterTest extends SolariumAdapterTest
{
    protected function getSolariumName()
    {
        return 'Solarium 2';
    }

    protected function getClientClass()
    {
        return 'Solarium_Client';
    }

    protected function getQueryClass()
    {
        return 'Solarium_Query_Select';
    }

    protected function getResultClass()
    {
        return 'Solarium_Result_Select';
    }
}