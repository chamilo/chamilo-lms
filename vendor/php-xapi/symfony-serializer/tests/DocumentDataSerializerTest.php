<?php

/*
 * This file is part of the xAPI package.
 *
 * (c) Christian Flothmann <christian.flothmann@xabbuh.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Xabbuh\XApi\Serializer\Symfony\Tests;

use Xabbuh\XApi\Serializer\Symfony\DocumentDataSerializer;
use Xabbuh\XApi\Serializer\Symfony\Serializer;
use Xabbuh\XApi\Serializer\Tests\DocumentDataSerializerTest as BaseDocumentDataSerializerTest;

class DocumentDataSerializerTest extends BaseDocumentDataSerializerTest
{
    protected function createDocumentDataSerializer()
    {
        return new DocumentDataSerializer(Serializer::createSerializer());
    }
}
