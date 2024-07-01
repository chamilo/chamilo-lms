<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class ThemeControllerTest extends WebTestCase
{

    public function testValidAccess(): void
    {
        $client = static::createClient();

        $client->request('GET', '/themes/chamilo/colors.css');

        $this->assertResponseIsSuccessful();
    }

    public function testInvalidAccess(): void
    {
        $client = static::createClient();

        $client->request('GET', '/themes/chamilo/default.css');

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testAccessToSystemFiles(): void
    {
        $client = static::createClient();
        $client->request('GET', '/themes/chamilo/../../../../../../etc/passwd');

        $this->assertResponseStatusCodeSame(Response::HTTP_INTERNAL_SERVER_ERROR);


        $client->request('GET', 'themes/chamilo/../../../.env');

        $this->assertResponseStatusCodeSame(Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}
