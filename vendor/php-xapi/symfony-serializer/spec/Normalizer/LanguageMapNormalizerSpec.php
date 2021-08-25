<?php

namespace spec\Xabbuh\XApi\Serializer\Symfony\Normalizer;

use PhpSpec\ObjectBehavior;
use Xabbuh\XApi\Model\LanguageMap;

class LanguageMapNormalizerSpec extends ObjectBehavior
{
    function it_is_a_normalizer()
    {
        $this->shouldHaveType('Symfony\Component\Serializer\Normalizer\NormalizerInterface');
    }

    function it_is_a_denormalizer()
    {
        $this->shouldHaveType('Symfony\Component\Serializer\Normalizer\DenormalizerInterface');
    }

    function it_supports_normalizing_language_map_objects()
    {
        $this->supportsNormalization(new LanguageMap())->shouldReturn(true);
    }

    function it_normalizes_language_map_instances_to_arrays()
    {
        $map = array(
            'de-DE' => 'teilgenommen',
            'en-GB' => 'attended',
            'en-US' => 'attended',
        );

        $normalizedMap = $this->normalize(LanguageMap::create($map));

        $normalizedMap->shouldBeArray();
        $normalizedMap->shouldHaveCount(3);
        $normalizedMap->shouldHaveKeyWithValue('de-DE', 'teilgenommen');
        $normalizedMap->shouldHaveKeyWithValue('en-GB', 'attended');
        $normalizedMap->shouldHaveKeyWithValue('en-US', 'attended');
    }

    function it_supports_denormalizing_to_language_map_objects()
    {
        $this->supportsDenormalization(array(), 'Xabbuh\XApi\Model\LanguageMap')->shouldReturn(true);
    }

    function it_denormalizes_arrays_to_language_map_instances()
    {
        $map = array(
            'de-DE' => 'teilgenommen',
            'en-GB' => 'attended',
            'en-US' => 'attended',
        );
        $languageMap = LanguageMap::create($map);

        $this->denormalize($map, 'Xabbuh\XApi\Model\LanguageMap')->equals($languageMap)->shouldReturn(true);
    }
}
