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
use Xabbuh\XApi\Serializer\ActorSerializerInterface;
use Xabbuh\XApi\Serializer\DocumentDataSerializerInterface;
use Xabbuh\XApi\Model\StateDocument;
use Xabbuh\XApi\Model\State;

/**
 * Client to access the state API of an xAPI based learning record store.
 *
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 */
final class StateApiClient extends DocumentApiClient implements StateApiClientInterface
{
    /**
     * @var ActorSerializerInterface
     */
    private $actorSerializer;

    /**
     * @param HandlerInterface                $requestHandler         The HTTP request handler
     * @param string                          $version                The xAPI version
     * @param DocumentDataSerializerInterface $documentDataSerializer The document data serializer
     * @param ActorSerializerInterface        $actorSerializer        The actor serializer
     */
    public function __construct(
        HandlerInterface $requestHandler,
        $version,
        DocumentDataSerializerInterface $documentDataSerializer,
        ActorSerializerInterface $actorSerializer
    ) {
        parent::__construct($requestHandler, $version, $documentDataSerializer);

        $this->actorSerializer = $actorSerializer;
    }

    /**
     * {@inheritDoc}
     */
    public function createOrUpdateDocument(StateDocument $document)
    {
        $this->doStoreStateDocument('post', $document);
    }

    /**
     * {@inheritDoc}
     */
    public function createOrReplaceDocument(StateDocument $document)
    {
        $this->doStoreStateDocument('put', $document);
    }

    /**
     * {@inheritDoc}
     */
    public function deleteDocument(State $state)
    {
        $this->doDeleteDocument('activities/state', array(
            'activityId' => $state->getActivity()->getId()->getValue(),
            'agent' => $this->actorSerializer->serializeActor($state->getActor()),
            'stateId' => $state->getStateId(),
        ));
    }

    /**
     * {@inheritDoc}
     */
    public function getDocument(State $state)
    {
        /** @var \Xabbuh\XApi\Model\DocumentData $documentData */
        $documentData = $this->doGetDocument('activities/state', array(
            'activityId' => $state->getActivity()->getId()->getValue(),
            'agent' => $this->actorSerializer->serializeActor($state->getActor()),
            'stateId' => $state->getStateId(),
        ));

        return new StateDocument($state, $documentData);
    }

    /**
     * Stores a state document.
     *
     * @param string        $method   HTTP method to use
     * @param StateDocument $document The document to store
     */
    private function doStoreStateDocument($method, StateDocument $document)
    {
        $state = $document->getState();
        $this->doStoreDocument(
            $method,
            'activities/state',
            array(
                'activityId' => $state->getActivity()->getId()->getValue(),
                'agent' => $this->actorSerializer->serializeActor($state->getActor()),
                'stateId' => $state->getStateId(),
            ),
            $document
        );
    }
}
