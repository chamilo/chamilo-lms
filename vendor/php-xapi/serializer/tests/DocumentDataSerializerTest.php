<?php

/*
 * This file is part of the xAPI package.
 *
 * (c) Christian Flothmann <christian.flothmann@xabbuh.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Xabbuh\XApi\Serializer\Tests;

use Xabbuh\XApi\DataFixtures\DocumentFixtures;
use XApi\Fixtures\Json\DocumentJsonFixtures;

/**
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 */
abstract class DocumentDataSerializerTest extends SerializerTest
{
    private $documentDataSerializer;

    protected function setUp()
    {
        $this->documentDataSerializer = $this->createDocumentDataSerializer();
    }

    public function testDeserializeDocumentData()
    {
        $documentData = $this->documentDataSerializer->deserializeDocumentData(DocumentJsonFixtures::getDocument());

        $this->assertInstanceOf('\Xabbuh\XApi\Model\DocumentData', $documentData);
        $this->assertEquals('foo', $documentData['x']);
        $this->assertEquals('bar', $documentData['y']);
    }

    public function testSerializeDocumentData()
    {
        $documentData = DocumentFixtures::getDocumentData();

        $this->assertJsonStringEqualsJsonString(
            DocumentJsonFixtures::getDocument(),
            $this->documentDataSerializer->serializeDocumentData($documentData)
        );
    }

    abstract protected function createDocumentDataSerializer();
}
