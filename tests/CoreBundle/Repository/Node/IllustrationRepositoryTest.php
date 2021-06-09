<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Repository\Node;

use Chamilo\CoreBundle\Entity\ResourceFile;
use Chamilo\CoreBundle\Repository\Node\IllustrationRepository;
use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @covers \IllustrationRepository
 */
class IllustrationRepositoryTest extends KernelTestCase
{
    use ChamiloTestTrait;

    public function testCreateCourseIllustration(): void
    {
        self::bootKernel();
        /** @var IllustrationRepository $repo */
        $repo = self::getContainer()->get(IllustrationRepository::class);

        $course = $this->createCourse('course');

        $file = $repo->addIllustration($course, $this->getUser('admin'), $this->getUploadedFile());
        $this->assertHasNoEntityViolations($file);
        $this->assertNotNull($file);
    }

    public function testCreateUserIllustration(): void
    {
        self::bootKernel();
        /** @var IllustrationRepository $repo */
        $repo = self::getContainer()->get(IllustrationRepository::class);

        $user = $this->createUser('test');

        $file = $repo->addIllustration($user, $user, $this->getUploadedFile());
        $this->assertHasNoEntityViolations($file);
        $this->assertNotNull($file);
    }
}
