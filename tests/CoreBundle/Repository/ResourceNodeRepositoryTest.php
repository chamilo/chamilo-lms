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
use Symfony\Component\Routing\RouterInterface;

class ResourceNodeRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testGetResourceNodeFileContent(): void
    {
        $em = $this->getEntityManager();
        $repo = self::getContainer()->get(ResourceNodeRepository::class);

        $repoType = $em->getRepository(ResourceType::class);
        $user = $this->createUser('julio');

        $type = $repoType->findOneBy(['title' => 'illustrations']);

        $resourceNode = (new ResourceNode())
            ->setContent('test')
            ->setTitle('test')
            ->setSlug('test')
            ->setResourceType($type)
            ->setCreator($user)
            ->setParent($user->getResourceNode())
        ;
        $em->persist($resourceNode);
        $em->flush();

        $content = $repo->getResourceNodeFileContent($resourceNode);
        $this->assertEmpty($content);

        $uploadedFile = $this->getUploadedFile();

        $resourceFile = (new ResourceFile())
            ->setTitle($uploadedFile->getFilename())
            ->setOriginalName($uploadedFile->getFilename())
            ->setFile($uploadedFile)
            ->setDescription('desc')
            ->setCrop('')
            ->setMetadata([])
        ;
        $em->persist($resourceFile);

        $resourceNode->setContent('')->addResourceFile($resourceFile);
        $em->persist($resourceNode);
        $em->flush();

        $this->assertSame($uploadedFile->getFilename(), (string) $resourceFile);
        $this->assertSame('desc', $resourceFile->getDescription());
        $this->assertNotEmpty($resourceFile->getWidth());
        $this->assertNotEmpty($resourceFile->getHeight());
        $this->assertIsArray($resourceFile->getMetadata());

        $this->assertSame('test', $resourceNode->getSlug());
        $this->assertTrue($resourceNode->isResourceFileAnImage());
        $this->assertFalse($resourceNode->isResourceFileAVideo());
        $this->assertNotEmpty(1, $resourceNode->getIcon());

        $router = $this->getContainer()->get(RouterInterface::class);
        $this->assertStringContainsString(
            '/r/asset/illustrations/'.$resourceNode->getId().'/view?filter=editor_thumbnail',
            $resourceNode->getThumbnail($router)
        );

        $content = $repo->getResourceNodeFileContent($resourceNode);
        $this->assertNotEmpty($content);
    }
}
