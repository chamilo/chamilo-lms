<?php

/*
 * This file is part of the xAPI package.
 *
 * (c) Christian Flothmann <christian.flothmann@xabbuh.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Xabbuh\XApi\Client\Tests\Api;

use Xabbuh\XApi\Client\Api\ActivityProfileApiClient;
use Xabbuh\XApi\DataFixtures\DocumentFixtures;
use Xabbuh\XApi\Model\Activity;
use Xabbuh\XApi\Model\ActivityProfile;
use Xabbuh\XApi\Model\ActivityProfileDocument;
use Xabbuh\XApi\Model\IRI;
use Xabbuh\XApi\Serializer\Symfony\DocumentDataSerializer;

/**
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 */
class ActivityProfileApiClientTest extends ApiClientTest
{
    /**
     * @var ActivityProfileApiClient
     */
    private $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = new ActivityProfileApiClient(
            $this->requestHandler,
            '1.0.1',
            new DocumentDataSerializer($this->serializer)
        );
    }

    public function testCreateOrUpdateDocument()
    {
        $document = DocumentFixtures::getActivityProfileDocument();

        $this->validateStoreApiCall(
            'post',
            'activities/profile',
            array(
                'activityId' => 'activity-id',
                'profileId' => 'profile-id',
            ),
            204,
            '',
            $document->getData()
        );

        $this->client->createOrUpdateDocument($document);
    }

    public function testCreateOrReplaceDocument()
    {
        $document = DocumentFixtures::getActivityProfileDocument();

        $this->validateStoreApiCall(
            'put',
            'activities/profile',
            array(
                'activityId' => 'activity-id',
                'profileId' => 'profile-id',
            ),
            204,
            '',
            $document->getData()
        );

        $this->client->createOrReplaceDocument($document);
    }

    public function testDeleteDocument()
    {
        $activityProfile = $this->createActivityProfile();

        $this->validateRequest(
            'delete',
            'activities/profile',
            array(
                'activityId' => 'activity-id',
                'profileId' => 'profile-id',
            ),
            ''
        );
        $this->validateSerializer(array());

        $this->client->deleteDocument($activityProfile);
    }

    public function testGetDocument()
    {
        $document = DocumentFixtures::getActivityProfileDocument();
        $activityProfile = $document->getActivityProfile();

        $this->validateRetrieveApiCall(
            'get',
            'activities/profile',
            array(
                'activityId' => 'activity-id',
                'profileId' => 'profile-id',
            ),
            200,
            'DocumentData',
            $document->getData()
        );

        $document = $this->client->getDocument($activityProfile);

        $this->assertInstanceOf(ActivityProfileDocument::class, $document);
        $this->assertEquals($activityProfile, $document->getActivityProfile());
    }

    private function createActivityProfile()
    {
        $activity = new Activity(IRI::fromString('activity-id'));
        $activityProfile = new ActivityProfile('profile-id', $activity);

        return $activityProfile;
    }
}
