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
final class ActivityProfileApiClient extends DocumentApiClient implements ActivityProfileApiClientInterface
{
    /**
     * {@inheritDoc}
     */
    public function createOrUpdateDocument(ActivityProfileDocument $document)
    {
        $this->doStoreActivityProfileDocument('post', $document);
    }

    /**
     * {@inheritDoc}
     */
    public function createOrReplaceDocument(ActivityProfileDocument $document)
    {
        $this->doStoreActivityProfileDocument('put', $document);
    }

    /**
     * {@inheritDoc}
     */
    public function deleteDocument(ActivityProfile $profile)
    {
        $this->doDeleteDocument('activities/profile', array(
            'activityId' => $profile->getActivity()->getId()->getValue(),
            'profileId' => $profile->getProfileId(),
        ));
    }

    /**
     * {@inheritDoc}
     */
    public function getDocument(ActivityProfile $profile)
    {
        /** @var \Xabbuh\XApi\Model\DocumentData $documentData */
        $documentData = $this->doGetDocument('activities/profile', array(
            'activityId' => $profile->getActivity()->getId()->getValue(),
            'profileId' => $profile->getProfileId(),
        ));

        return new ActivityProfileDocument($profile, $documentData);
    }

    /**
     * Stores a state document.
     *
     * @param string                  $method   HTTP method to use
     * @param ActivityProfileDocument $document The document to store
     */
    private function doStoreActivityProfileDocument($method, ActivityProfileDocument $document)
    {
        $profile = $document->getActivityProfile();
        $this->doStoreDocument(
            $method,
            'activities/profile',
            array(
                'activityId' => $profile->getActivity()->getId()->getValue(),
                'profileId' => $profile->getProfileId(),
            ),
            $document
        );
    }
}
