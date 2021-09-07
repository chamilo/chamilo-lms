<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\ResourceFile;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\ResourceType;
use Chamilo\CoreBundle\Repository\ResourceNodeRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

class ResourceNodeRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testCreate(): void
    {
        self::bootKernel();

        $em = $this->getManager();
        $repo = self::getContainer()->get(ResourceNodeRepository::class);

        $repoType = $em->getRepository(ResourceType::class);
        $user = $this->createUser('julio');

        $defaultCount = $repo->count([]);

        $type = $repoType->findOneBy(['name' => 'illustrations']);

        $node = (new ResourceNode())
            ->setContent('test')
            ->setTitle('test')
            ->setSlug('test')
            ->setResourceType($type)
            ->setCreator($user)
            ->setParent($user->getResourceNode())
        ;
        $this->assertHasNoEntityViolations($node);

        $em->persist($node);
        $em->flush();

        $this->assertSame($defaultCount + 1, $repo->count([]));
    }

    public function testGetResourceNodeFileContent(): void
    {
        $em = $this->getManager();
        $repo = self::getContainer()->get(ResourceNodeRepository::class);

        $repoType = $em->getRepository(ResourceType::class);
        $user = $this->createUser('julio');

        $type = $repoType->findOneBy(['name' => 'illustrations']);

        $node = (new ResourceNode())
            ->setContent('test')
            ->setTitle('test')
            ->setSlug('test')
            ->setResourceType($type)
            ->setCreator($user)
            ->setParent($user->getResourceNode())
        ;
        $em->persist($node);
        $em->flush();

        $content = $repo->getResourceNodeFileContent($node);
        $this->assertEmpty($content);

        $uploadedFile = $this->getUploadedFile();

        $resourceFile = (new ResourceFile())
            ->setName($uploadedFile->getFilename())
            ->setOriginalName($uploadedFile->getFilename())
            ->setFile($uploadedFile)
        ;
        $em->persist($resourceFile);

        $node->setContent('')->setResourceFile($resourceFile);
        $em->persist($node);
        $em->flush();

        $content = $repo->getResourceNodeFileContent($node);
        $this->assertNotEmpty($content);
    }
}
