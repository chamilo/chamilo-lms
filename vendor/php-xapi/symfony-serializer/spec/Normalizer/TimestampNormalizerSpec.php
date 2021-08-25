<?php

namespace spec\Xabbuh\XApi\Serializer\Symfony\Normalizer;

use PhpSpec\ObjectBehavior;

class TimestampNormalizerSpec extends ObjectBehavior
{
    function it_is_a_normalizer()
    {
        $this->shouldHaveType('Symfony\Component\Serializer\Normalizer\NormalizerInterface');
    }

    function it_can_normalize_datetime_objects()
    {
        $this->supportsNormalization(new \DateTime())->shouldBe(true);
    }

    function it_cannot_normalize_datetime_like_string()
    {
        $this->supportsNormalization('2004-02-12T15:19:21+00:00')->shouldBe(false);
    }

    function it_normalizes_datetime_objects_as_iso_8601_formatted_strings()
    {
        $date = new \DateTime();
        $date->setTimezone(new \DateTimeZone('UTC'));
        $date->setDate(2004, 2, 12);
        $date->setTime(15, 19, 21);

        $this->normalize($date)->shouldReturn('2004-02-12T15:19:21+00:00');
    }

    function it_throws_an_exception_when_data_other_than_datetime_objects_are_passed()
    {
        $this->shouldThrow('Symfony\Component\Serializer\Exception\InvalidArgumentException')->during('normalize', array('2004-02-12T15:19:21+00:00'));
    }

    function it_is_a_denormalizer()
    {
        $this->shouldHaveType('Symfony\Component\Serializer\Normalizer\DenormalizerInterface');
    }

    function it_can_denormalize_to_datetime_objects()
    {
        $this->supportsDenormalization('2004-02-12T15:19:21+00:00', 'DateTime')->shouldBe(true);
    }

    function it_denormalizes_iso_8601_formatted_strings_to_datetime_objects()
    {
        $date = $this->denormalize('2004-02-12T15:19:21+00:00', 'DateTime');

        $date->getTimezone()->shouldBeLike(new \DateTimeZone('UTC'));
        $date->format('Y-m-d H:i:s')->shouldReturn('2004-02-12 15:19:21');
    }
}
