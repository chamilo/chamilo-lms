<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Controller;

use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ExceptionControllerTest extends WebTestCase
{
    use ChamiloTestTrait;

    public function testError(): void
    {
        $client = static::createClient();
        $client->request('GET', '/error');
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Something seems to have gone wrong', $client->getResponse()->getContent());
    }
}
