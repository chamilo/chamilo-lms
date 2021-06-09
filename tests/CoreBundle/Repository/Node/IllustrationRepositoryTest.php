<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Repository\Node;

use Chamilo\CoreBundle\Repository\Node\IllustrationRepository;
use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @covers \IllustrationRepository
 */
class IllustrationRepositoryTest extends WebTestCase
{
    use ChamiloTestTrait;

    public function testCreateCourseIllustration(): void
    {
        $client = static::createClient();

        /** @var IllustrationRepository $repo */
        $repo = self::getContainer()->get(IllustrationRepository::class);

        $course = $this->createCourse('course');
        $file = $repo->addIllustration($course, $this->getUser('admin'), $this->getUploadedFile());

        $this->assertHasNoEntityViolations($file);
        $this->assertNotNull($file);

        $url = $repo->getIllustrationUrl($course);

        $client->request(
            'GET',
            $url
        );
        $this->assertResponseIsSuccessful();
    }

    public function testCreateUserIllustration(): void
    {
        $client = static::createClient();

        /** @var IllustrationRepository $repo */
        $repo = self::getContainer()->get(IllustrationRepository::class);

        $user = $this->createUser('test');

        $file = $repo->addIllustration($user, $user, $this->getUploadedFile());
        $this->assertHasNoEntityViolations($file);
        $this->assertNotNull($file);

        $url = $repo->getIllustrationUrl($user);
        $client->request(
            'GET',
            $url
        );
        $this->assertResponseIsSuccessful();
    }
}
