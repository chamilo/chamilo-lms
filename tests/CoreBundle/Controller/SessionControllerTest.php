<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Controller;

use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

class SessionControllerTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testAbout(): void
    {
        self::bootKernel();

        $session = $this->createSession('session 1');

        $tokenFrom = $this->getUserToken(
            [
                'username' => 'admin',
                'password' => 'admin',
            ]
        );

        $response = $this->createClientWithCredentials($tokenFrom)->request(
            'POST',
            '/sessions/'.$session->getId().'/about'
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);
        $this->assertStringContainsString('session 1', $response->getContent());
    }
}
