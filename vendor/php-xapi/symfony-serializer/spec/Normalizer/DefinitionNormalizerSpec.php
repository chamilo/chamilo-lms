<?php

namespace spec\Xabbuh\XApi\Serializer\Symfony\Normalizer;

use PhpSpec\ObjectBehavior;
use Xabbuh\XApi\Model\Definition;

class DefinitionNormalizerSpec extends ObjectBehavior
{
    function it_is_a_normalizer()
    {
        $this->shouldHaveType('Symfony\Component\Serializer\Normalizer\NormalizerInterface');
    }

    function it_supports_normalizing_definition_objects()
    {
        $this->supportsNormalization(new Definition())->shouldReturn(true);
    }

    function it_is_a_denormalizer()
    {
        $this->shouldHaveType('Symfony\Component\Serializer\Normalizer\DenormalizerInterface');
    }

    function it_supports_denormalizing_to_definition_objects()
    {
        $this->supportsDenormalization(array(), 'Xabbuh\XApi\Model\Definition')->shouldReturn(true);
    }

    function it_supports_denormalizing_to_interaction_definition_objects()
    {
        $this->supportsDenormalization(array(), 'Xabbuh\XApi\Model\Interaction\ChoiceInteractionDefinition')->shouldReturn(true);
        $this->supportsDenormalization(array(), 'Xabbuh\XApi\Model\Interaction\FillInInteractionDefinition')->shouldReturn(true);
        $this->supportsDenormalization(array(), 'Xabbuh\XApi\Model\Interaction\LikertInteractionDefinition')->shouldReturn(true);
        $this->supportsDenormalization(array(), 'Xabbuh\XApi\Model\Interaction\LongFillInInteractionDefinition')->shouldReturn(true);
        $this->supportsDenormalization(array(), 'Xabbuh\XApi\Model\Interaction\MatchingInteractionDefinition')->shouldReturn(true);
        $this->supportsDenormalization(array(), 'Xabbuh\XApi\Model\Interaction\NumericInteractionDefinition')->shouldReturn(true);
        $this->supportsDenormalization(array(), 'Xabbuh\XApi\Model\Interaction\OtherInteractionDefinition')->shouldReturn(true);
        $this->supportsDenormalization(array(), 'Xabbuh\XApi\Model\Interaction\PerformanceInteractionDefinition')->shouldReturn(true);
        $this->supportsDenormalization(array(), 'Xabbuh\XApi\Model\Interaction\SequencingInteractionDefinition')->shouldReturn(true);
        $this->supportsDenormalization(array(), 'Xabbuh\XApi\Model\Interaction\TrueFalseInteractionDefinition')->shouldReturn(true);
    }

    function it_throws_an_exception_when_an_unknown_interaction_type_should_be_denormalized()
    {
        $this->shouldThrow('Symfony\Component\Serializer\Exception\InvalidArgumentException')->during('denormalize', array(
            array('interactionType' => 'foo'),
            'Xabbuh\XApi\Model\Definition'
        ));
    }
}
