<?php

namespace spec\Xabbuh\XApi\Serializer\Symfony\Normalizer;

use PhpSpec\ObjectBehavior;
use Xabbuh\XApi\Model\IRI;
use Xabbuh\XApi\Model\Verb;

class VerbNormalizerSpec extends ObjectBehavior
{
    function it_is_a_normalizer()
    {
        $this->shouldHaveType('Symfony\Component\Serializer\Normalizer\NormalizerInterface');
    }

    function it_supports_normalizing_verb_objects()
    {
        $this->supportsNormalization(new Verb(IRI::fromString('http://tincanapi.com/conformancetest/verbid')))->shouldReturn(true);
    }

    function it_is_a_denormalizer()
    {
        $this->shouldHaveType('Symfony\Component\Serializer\Normalizer\DenormalizerInterface');
    }

    function it_supports_denormalizing_to_verb_objects()
    {
        $this->supportsDenormalization(array(), 'Xabbuh\XApi\Model\Verb')->shouldReturn(true);
    }
}
