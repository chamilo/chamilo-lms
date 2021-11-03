<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Repository\Node;

use Chamilo\CoreBundle\Entity\Illustration;
use Chamilo\CoreBundle\Repository\Node\IllustrationRepository;
use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

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
        $this->assertSame(1, $repo->count([]));
        $this->assertTrue($repo->hasIllustration($course));

        $repo->deleteIllustration($course);
        $this->assertSame(0, $repo->count([]));
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

    public function testCreateIllustration(): void
    {
        $repo = self::getContainer()->get(IllustrationRepository::class);
        $user = $this->createUser('test');

        $illustration = (new Illustration())
            ->setName('test')
            ->setResourceName('test')
            ->setCreator($user)
            ->setParent($user)
        ;
        $repo->addResourceNode($illustration, $user, $user);
        $repo->update($illustration);

        $this->assertSame('test', (string) $illustration);
        $this->assertNotNull($illustration->getId());
        $this->assertSame($illustration->getId(), $illustration->getResourceIdentifier());
    }
}
