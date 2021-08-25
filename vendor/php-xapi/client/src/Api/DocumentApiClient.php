<?php

/*
 * This file is part of the xAPI package.
 *
 * (c) Christian Flothmann <christian.flothmann@xabbuh.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Xabbuh\XApi\Client\Api;

use Xabbuh\XApi\Client\Request\HandlerInterface;
use Xabbuh\XApi\Model\Document;
use Xabbuh\XApi\Model\DocumentData;
use Xabbuh\XApi\Serializer\DocumentDataSerializerInterface;

/**
 * Base class for the document API classes.
 *
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 */
abstract class DocumentApiClient
{
    private $requestHandler;
    private $version;
    private $documentDataSerializer;

    /**
     * @param HandlerInterface                $requestHandler         The HTTP request handler
     * @param string                          $version                The xAPI version
     * @param DocumentDataSerializerInterface $documentDataSerializer The document data serializer
     */
    public function __construct(HandlerInterface $requestHandler, $version, DocumentDataSerializerInterface $documentDataSerializer)
    {
        $this->requestHandler = $requestHandler;
        $this->version = $version;
        $this->documentDataSerializer = $documentDataSerializer;
    }

    /**
     * Stores a document.
     *
     * @param string   $method        HTTP method to use
     * @param string   $uri           Endpoint URI
     * @param array    $urlParameters URL parameters
     * @param Document $document      The document to store
     */
    protected function doStoreDocument($method, $uri, $urlParameters, Document $document)
    {
        $request = $this->requestHandler->createRequest(
            $method,
            $uri,
            $urlParameters,
            $this->documentDataSerializer->serializeDocumentData($document->getData())
        );
        $this->requestHandler->executeRequest($request, array(204));
    }

    /**
     * Deletes a document.
     *
     * @param string $uri           The endpoint URI
     * @param array  $urlParameters The URL parameters
     */
    protected function doDeleteDocument($uri, array $urlParameters)
    {
        $request = $this->requestHandler->createRequest('delete', $uri, $urlParameters);
        $this->requestHandler->executeRequest($request, array(204));
    }

    /**
     * Returns a document.
     *
     * @param string $uri           The endpoint URI
     * @param array  $urlParameters The URL parameters
     *
     * @return Document The document
     */
    protected function doGetDocument($uri, array $urlParameters)
    {
        $request = $this->requestHandler->createRequest('get', $uri, $urlParameters);
        $response = $this->requestHandler->executeRequest($request, array(200));
        $document = $this->deserializeDocument((string) $response->getBody());

        return $document;
    }

    /**
     * Deserializes the data of a document.
     *
     * @param string $data The serialized document data
     *
     * @return DocumentData The parsed document data
     */
    protected function deserializeDocument($data)
    {
        return $this->documentDataSerializer->deserializeDocumentData($data);
    }
}
