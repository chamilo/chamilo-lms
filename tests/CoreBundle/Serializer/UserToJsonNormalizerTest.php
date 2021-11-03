<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Serializer;

use Chamilo\CoreBundle\Serializer\UserToJsonNormalizer;
use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UserToJsonNormalizerTest extends WebTestCase
{
    use ChamiloTestTrait;

    public function testSerialize(): void
    {
        $serializer = static::getContainer()->get(UserToJsonNormalizer::class);

        $student = $this->createUser('student');
        $result = $serializer->getPersonalDataToJson($student->getId(), []);
        $this->assertStringContainsString('student', $result);
    }
}
