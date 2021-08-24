<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Repository\Node;

use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

class PersonalFileRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testAccessAsAnon(): void
    {
        $admin = $this->getUser('admin');

        $client = static::createClient();
        $client->request('GET', '/api/personal_files');
        $this->assertResponseStatusCodeSame(401);

        $client->request('POST', '/api/personal_files');
        $this->assertResponseStatusCodeSame(401);

        $client->request(
            'GET',
            '/api/personal_files',
            [
                'json' => [
                    'resourceNode.parent' => $admin->getResourceNode()->getId(),
                ],
            ]
        );
        $this->assertResponseStatusCodeSame(401);
    }

    public function testCreateFolder(): void
    {
        $username = 'test';
        $password = 'test';

        $user = $this->createUser($username, $password);
        $folderName = 'folder1';
        $token = $this->getUserToken(
            [
                'username' => $username,
                'password' => $password,
            ]
        );

        $resourceNodeId = $user->getResourceNode()->getId();

        $this->createClientWithCredentials($token)->request(
            'POST',
            '/api/personal_files',
            [
                'json' => [
                    'filetype' => 'folder',
                    'title' => $folderName,
                    'parentResourceNodeId' => $resourceNodeId,
                ],
            ]
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains(
            [
                '@context' => '/api/contexts/PersonalFile',
                '@type' => 'PersonalFile',
                'title' => $folderName,
                'parentResourceNode' => $resourceNodeId,
            ]
        );
    }

    public function testFileUploadAndShare(): void
    {
        self::bootKernel();
        $username = 'sender';
        $password = 'sender';
        $visibilityPublished = ResourceLink::VISIBILITY_PUBLISHED;

        // Creates "sender" user.
        $user = $this->createUser($username, $password);
        $token = $this->getUserToken(
            [
                'username' => $username,
                'password' => $password,
            ]
        );

        // Creates "receiver" user.
        $receiverUsername = 'receiver';
        $receiverPassword = 'receiver';
        $receiverUser = $this->createUser($receiverUsername, $receiverPassword);
        $resourceNodeId = $user->getResourceNode()->getId();

        $file = $this->getUploadedFile();
        $fileName = $file->getFilename();

        // Upload file.
        $response = $this->createClientWithCredentials($token)->request(
            'POST',
            '/api/personal_files',
            [
                'headers' => [
                    'Content-Type' => 'multipart/form-data',
                ],
                'extra' => [
                    'files' => [
                        'uploadFile' => $file,
                    ],
                ],
                'json' => [
                    'filetype' => 'file',
                    'size' => $file->getSize(),
                    'parentResourceNodeId' => $resourceNodeId,
                ],
            ]
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains(
            [
                '@context' => '/api/contexts/PersonalFile',
                '@type' => 'PersonalFile',
                'title' => $fileName,
                'parentResourceNode' => $resourceNodeId,
            ]
        );

        // File URL.
        $url = $response->toArray()['contentUrl'];
        $personalFileId = $response->toArray()['id'];

        $resourceLinkList = [
            [
                'uid' => $receiverUser->getId(),
                'visibility' => $visibilityPublished,
            ],
        ];

        // Share PersonalFile with user 'receiver'.
        $this->createClientWithCredentials($token)->request(
            'PUT',
            '/api/personal_files/'.$personalFileId,
            [
                'json' => [
                    'resourceLinkListFromEntity' => $resourceLinkList,
                ],
            ]
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains(
            [
                '@context' => '/api/contexts/PersonalFile',
                '@type' => 'PersonalFile',
                'title' => $fileName,
                'id' => $personalFileId,
                'resourceLinkListFromEntity' => [
                    [
                        'visibility' => $visibilityPublished,
                        'session' => null,
                        'course' => null,
                        'group' => null,
                        'userGroup' => null,
                        'user' => [
                            'id' => $receiverUser->getId(),
                        ],
                    ],
                ],
            ]
        );

        // Access Checks.

        // 1. Access file as anon. Result: redirects to the login.
        $this->createClient()->request(
            'GET',
            $url
        );
        $this->assertResponseRedirects('/login');

        // 2. Access file as another user. Result: forbidden access.
        $this->createUser('another', 'another');
        $client = $this->getClientWithGuiCredentials('another', 'another');
        $client->request(
            'GET',
            $url
        );
        $this->assertResponseStatusCodeSame(403); // unauthorized

        // 3. Access with the creator user should be allowed.
        $client = $this->getClientWithGuiCredentials($username, $password);
        $client->request(
            'GET',
            $url
        );
        $this->assertResponseIsSuccessful();

        // 4. Access with admin should be allowed.
        $client = $this->getClientWithGuiCredentials('admin', 'admin');
        $client->request(
            'GET',
            $url
        );
        $this->assertResponseIsSuccessful();

        // 5. Access with receiver user. Result: Should be allowed because it was shared.
        $client = $this->getClientWithGuiCredentials($receiverUsername, $receiverPassword);
        $client->request(
            'GET',
            $url
        );
        $this->assertResponseIsSuccessful();
    }

    public function testUserUploadFileAsAnotherUser(): void
    {
        self::bootKernel();
        $username = 'sender';
        $password = 'sender';

        // Creates "sender" user.
        $user = $this->createUser($username, $password);
        $token = $this->getUserToken(
            [
                'username' => $username,
                'password' => $password,
            ]
        );

        // Add a folder.

        // 1. This is the original user.
        $resourceNodeId = $user->getResourceNode()->getId();

        $this->createClientWithCredentials($token)->request(
            'POST',
            '/api/personal_files',
            [
                'json' => [
                    'filetype' => 'folder',
                    'title' => 'temp',
                    'parentResourceNodeId' => $resourceNodeId,
                ],
            ]
        );
        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(201);

        $this->createUser('bad', 'bad');
        $badUserToken = $this->getUserToken(
            [
                'username' => 'bad',
                'password' => 'bad',
            ],
            true
        );

        $file = $this->getUploadedFile();

        // 2. "bad user" tries to upload file to the original "sender" personal list.
        $this->createClientWithCredentials($badUserToken)->request(
            'POST',
            '/api/personal_files',
            [
                'headers' => [
                    'Content-Type' => 'multipart/form-data',
                ],
                'extra' => [
                    'files' => [
                        'uploadFile' => $file,
                    ],
                ],
                'json' => [
                    'filetype' => 'file',
                    'size' => $file->getSize(),
                    'parentResourceNodeId' => $resourceNodeId,
                ],
            ]
        );
        $this->assertResponseStatusCodeSame(500);

        // Bad user tries to get files from other user, this should return an empty array
        $this->createClientWithCredentials($badUserToken)->request(
            'GET',
            '/api/personal_files',
            [
                'json' => [
                    'parentResourceNodeId' => $resourceNodeId,
                ],
            ]
        );

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => '/api/contexts/PersonalFile',
            '@id' => '/api/personal_files',
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => 0,
        ]);
    }
}
