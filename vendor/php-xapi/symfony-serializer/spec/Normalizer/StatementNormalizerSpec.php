<?php

namespace spec\Xabbuh\XApi\Serializer\Symfony\Normalizer;

use PhpSpec\ObjectBehavior;
use Xabbuh\XApi\DataFixtures\StatementFixtures;
use XApi\Fixtures\Json\StatementJsonFixtures;

class StatementNormalizerSpec extends ObjectBehavior
{
    function it_is_a_normalizer()
    {
        $this->shouldHaveType('Symfony\Component\Serializer\Normalizer\NormalizerInterface');
    }

    function it_is_a_denormalizer()
    {
        $this->shouldHaveType('Symfony\Component\Serializer\Normalizer\DenormalizerInterface');
    }

    function it_supports_normalizing_statements()
    {
        $this->supportsNormalization(StatementFixtures::getMinimalStatement())->shouldBe(true);
    }

    function it_supports_denormalizing_statements()
    {
        $this->supportsDenormalization(StatementJsonFixtures::getMinimalStatement(), 'Xabbuh\XApi\Model\Statement')->shouldBe(true);
    }
}
