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

use Xabbuh\XApi\Model\ActivityProfile;
use Xabbuh\XApi\Model\ActivityProfileDocument;

/**
 * Client to access the activity profile API of an xAPI based learning record
 * store.
 *
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 */
interface ActivityProfileApiClientInterface
{
    /**
     * Stores a document for an activity profile. Updates an existing document
     * for this activity profile if one exists.
     *
     * @param ActivityProfileDocument $document The document to store
     */
    public function createOrUpdateDocument(ActivityProfileDocument $document);

    /**
     * Stores a document for an activity profile. Replaces any existing document
     * for this activity profile.
     *
     * @param ActivityProfileDocument $document The document to store
     */
    public function createOrReplaceDocument(ActivityProfileDocument $document);

    /**
     * Deletes a document stored for the given activity profile.
     *
     * @param ActivityProfile $profile The activity profile
     */
    public function deleteDocument(ActivityProfile $profile);

    /**
     * Returns the document for an activity profile.
     *
     * @param ActivityProfile $profile The activity profile to request the
     *                                 document for
     *
     * @return ActivityProfileDocument The document
     */
    public function getDocument(ActivityProfile $profile);
}
