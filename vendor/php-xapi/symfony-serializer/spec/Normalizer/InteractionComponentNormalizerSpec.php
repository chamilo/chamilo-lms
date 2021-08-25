<?php

namespace spec\Xabbuh\XApi\Serializer\Symfony\Normalizer;

use PhpSpec\ObjectBehavior;
use Xabbuh\XApi\Model\Interaction\InteractionComponent;

class InteractionComponentNormalizerSpec extends ObjectBehavior
{
    function it_is_a_normalizer()
    {
        $this->shouldHaveType('Symfony\Component\Serializer\Normalizer\NormalizerInterface');
    }

    function it_supports_normalizing_interaction_component_objects()
    {
        $this->supportsNormalization(new InteractionComponent('test'))->shouldReturn(true);
    }

    function it_is_a_denormalizer()
    {
        $this->shouldHaveType('Symfony\Component\Serializer\Normalizer\DenormalizerInterface');
    }

    function it_supports_denormalizing_to_interaction_component_objects()
    {
        $this->supportsDenormalization(array(), 'Xabbuh\XApi\Model\Interaction\InteractionComponent')->shouldReturn(true);
    }
}
