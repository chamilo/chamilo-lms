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

use Xabbuh\XApi\Model\AgentProfile;
use Xabbuh\XApi\Model\AgentProfileDocument;

/**
 * Client to access the agent profile API of an xAPI based learning record
 * store.
 *
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 */
interface AgentProfileApiClientInterface
{
    /**
     * Stores a document for an agent profile. Updates an existing document for
     * this agent profile if one exists.
     *
     * @param AgentProfileDocument $document The document to store
     */
    public function createOrUpdateDocument(AgentProfileDocument $document);

    /**
     * Stores a document for an agent profile. Replaces any existing document
     * for this agent profile.
     *
     * @param AgentProfileDocument $document The document to store
     */
    public function createOrReplaceDocument(AgentProfileDocument $document);

    /**
     * Deletes a document stored for the given agent profile.
     *
     * @param AgentProfile $profile The agent profile
     */
    public function deleteDocument(AgentProfile $profile);

    /**
     * Returns the document for an agent profile.
     *
     * @param AgentProfile $profile The agent profile to request the document for
     *
     * @return AgentProfileDocument The document
     */
    public function getDocument(AgentProfile $profile);
}
