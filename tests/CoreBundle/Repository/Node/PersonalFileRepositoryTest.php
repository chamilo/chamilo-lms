<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Repository\Node;

use Chamilo\CoreBundle\Repository\Node\PersonalFileRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Component\HttpFoundation\File\UploadedFile;

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

        $user = $this->createUser($username, $password, 'test@test.com');
        $token = $this->getUserToken([
            'username' => $username,
            'password' => $password,
        ]);

        $path = $this->getContainer()->get('kernel')->getProjectDir();
        $filePath = $path.'/public/img/logo.png';
        $fileName = basename($filePath);
        $resourceNodeId = $user->getResourceNode()->getId();

        $file = new UploadedFile(
            $filePath,
            $fileName,
            'image/png',
        );

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
                    'size' => filesize($filePath),
                    'parentResourceNodeId' => $resourceNodeId,
                    //'resourceLinkList' => json_encode($resourceLinkList),
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

        // Access file as anon.
        $this->createClient()->request(
            'GET',
            $url
        );
        $this->assertResponseRedirects('/login');

        // Access file as another user should be forbidden.
        $this->createUser('another', 'another', 'another@test.com');
        $client = $this->getClientWithGuiCredentials('another', 'another');
        $client->request(
            'GET',
            $url
        );
        $this->assertResponseStatusCodeSame(403); // forbidden

        // Acces with the same user should be allowed.
        $client = $this->getClientWithGuiCredentials($username, $password);
        // Access file as user that created the file.
        $client->request(
            'GET',
            $url
        );
        $this->assertResponseIsSuccessful();
    }
}
