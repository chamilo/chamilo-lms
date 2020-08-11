<?php

namespace spec\Ddeboer\DataImport\Reader\Factory;

use Doctrine\DBAL\Connection;
use PhpSpec\ObjectBehavior;

class DbalReaderFactorySpec extends ObjectBehavior
{
    function let(Connection $dbal)
    {
        $this->beConstructedWith($dbal);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Ddeboer\DataImport\Reader\Factory\DbalReaderFactory');
    }

    function it_creates_a_reader()
    {
        $this->getReader('SQL', [])->shouldHaveType('Ddeboer\DataImport\Reader\DbalReader');
    }
}
