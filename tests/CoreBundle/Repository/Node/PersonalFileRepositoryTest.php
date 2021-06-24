<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Repository\Node;

use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

/**
 * @covers \PersonalFileRepository
 */
class PersonalFileRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testFileUpload(): void
    {
        self::bootKernel();
        $username = 'test';
        $password = 'test';

        $user = $this->createUser($username, $password);
        $token = $this->getUserToken([
            'username' => $username,
            'password' => $password,
        ]);

        $resourceNodeId = $user->getResourceNode()->getId();

        $file = $this->getUploadedFile();
        $fileName = $file->getFilename();

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
        $this->assertJsonContains([
            '@context' => '/api/contexts/PersonalFile',
            '@type' => 'PersonalFile',
            'title' => $fileName,
            'parentResourceNode' => $resourceNodeId,
        ]);

        $url = $response->toArray()['contentUrl'];

        // Access file as anon, redirects to the login.
        $this->createClient()->request(
            'GET',
            $url
        );
        $this->assertResponseRedirects('/login');

        // Access file as another user should be forbidden.
        $this->createUser('another', 'another');
        $client = $this->getClientWithGuiCredentials('another', 'another');
        $client->request(
            'GET',
            $url
        );
        $this->assertResponseStatusCodeSame(403); // unauthorized

        // Access with the same user should be allowed.
        $client = $this->getClientWithGuiCredentials($username, $password);
        // Access file as user that created the file.
        $client->request(
            'GET',
            $url
        );
        $this->assertResponseIsSuccessful();

        // Access with admin should be allowed.
        $client = $this->getClientWithGuiCredentials('admin', 'admin');
        $client->request(
            'GET',
            $url
        );
        $this->assertResponseIsSuccessful();
    }
}
