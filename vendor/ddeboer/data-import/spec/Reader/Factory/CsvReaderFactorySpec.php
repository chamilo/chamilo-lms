<?php

namespace spec\Ddeboer\DataImport\Reader\Factory;

use PhpSpec\ObjectBehavior;

class CsvReaderFactorySpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Ddeboer\DataImport\Reader\Factory\CsvReaderFactory');
    }

    function it_creates_a_reader()
    {
        $file = new \SplFileObject(tempnam(sys_get_temp_dir(), null));

        $this->getReader($file)->shouldHaveType('Ddeboer\DataImport\Reader\CsvReader');
    }
}
