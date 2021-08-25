<?php

namespace spec\Xabbuh\XApi\Serializer\Symfony\Normalizer;

use PhpSpec\ObjectBehavior;
use Xabbuh\XApi\Model\Attachment;
use Xabbuh\XApi\Model\IRI;
use Xabbuh\XApi\Model\LanguageMap;

class AttachmentNormalizerSpec extends ObjectBehavior
{
    function it_is_a_normalizer()
    {
        $this->shouldHaveType('Symfony\Component\Serializer\Normalizer\NormalizerInterface');
    }

    function it_supports_normalizing_attachment_objects()
    {
        $attachment = new Attachment(
            IRI::fromString('http://id.tincanapi.com/attachment/supporting_media'),
            'text/plain',
            18,
            'bd1a58265d96a3d1981710dab8b1e1ed04a8d7557ea53ab0cf7b44c04fd01545',
            LanguageMap::create(array('en-US' => 'Text attachment')),
            null,
            null,
            'some text content'
        );

        $this->supportsNormalization($attachment)->shouldReturn(true);
    }

    function it_is_a_denormalizer()
    {
        $this->shouldHaveType('Symfony\Component\Serializer\Normalizer\DenormalizerInterface');
    }

    function it_supports_denormalizing_to_attachment_objects()
    {
        $this->supportsDenormalization(array(), 'Xabbuh\XApi\Model\Attachment')->shouldReturn(true);
    }
}
