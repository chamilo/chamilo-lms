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

use Xabbuh\XApi\Model\StateDocument;
use Xabbuh\XApi\Model\State;

/**
 * Client to access the state API of an xAPI based learning record store.
 *
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 */
interface StateApiClientInterface
{
    /**
     * Stores a document for a state. Updates an existing document for this
     * state if one exists.
     *
     * @param StateDocument $document The document to store
     */
    public function createOrUpdateDocument(StateDocument $document);

    /**
     * Stores a document for a state. Replaces any existing document for this
     * state.
     *
     * @param StateDocument $document The document to store
     */
    public function createOrReplaceDocument(StateDocument $document);

    /**
     * Deletes a document stored for the given state.
     *
     * @param State $state The state
     */
    public function deleteDocument(State $state);

    /**
     * Returns the document for a state.
     *
     * @param State $state The state to request the document for
     *
     * @return StateDocument The document
     */
    public function getDocument(State $state);
}
