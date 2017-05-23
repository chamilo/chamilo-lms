<?php

namespace spec\Ddeboer\DataImport\Reader\Factory;

use Doctrine\Common\Persistence\ObjectManager;
use PhpSpec\ObjectBehavior;

class DoctrineReaderFactorySpec extends ObjectBehavior
{
    function let(ObjectManager $objectManager)
    {
        $this->beConstructedWith($objectManager);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Ddeboer\DataImport\Reader\Factory\DoctrineReaderFactory');
    }

    function it_creates_a_reader()
    {
        $this->getReader('Entity')->shouldHaveType('Ddeboer\DataImport\Reader\DoctrineReader');
    }
}
