<?php

namespace MediaVorus\Media;

use Doctrine\Common\Annotations\AnnotationRegistry;
use JMS\Serializer\SerializerBuilder;
use MediaVorus\TestCase;

class MediaTestCase extends TestCase
{
    protected function getSerializer()
    {
        AnnotationRegistry::registerAutoloadNamespace(
            'JMS\Serializer\Annotation', __DIR__ . '/../../../../vendor/jms/serializer/src'
        );

        return SerializerBuilder::create()
            ->setCacheDir(__DIR__ . '/../../../../cache')
            ->setDebug(true)
            ->build();
    }
}

