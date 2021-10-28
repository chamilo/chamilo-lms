<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\ResourceComment;
use Chamilo\CoreBundle\Entity\ResourceFile;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\ResourceType;
use Chamilo\CoreBundle\Repository\ResourceNodeRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Routing\RouterInterface;

class ResourceNodeRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testCreate(): void
    {
        $em = $this->getEntityManager();
        $repo = self::getContainer()->get(ResourceNodeRepository::class);

        $repoType = $em->getRepository(ResourceType::class);
        $user = $this->createUser('julio');

        $defaultCount = $repo->count([]);

        $type = $repoType->findOneBy(['name' => 'illustrations']);
        $resourceNode = (new ResourceNode())
            ->setContent('test')
            ->setTitle('test')
            ->setSlug('test')
            ->setResourceType($type)
            ->setCreator($user)
            ->setPublic(true)
            ->setParent($user->getResourceNode())
        ;
        $this->assertHasNoEntityViolations($resourceNode);
        $em->persist($resourceNode);
        $em->flush();

        $this->assertNotNull((string) $resourceNode);
        $this->assertSame(2, $resourceNode->getLevel());
        $path = $user->getResourceNode()->getSlug().'-'.$user->getResourceNode()->getId().'/'.$resourceNode->getSlug().'-'.$resourceNode->getId().'/';
        $this->assertSame($path, $resourceNode->getPathForDisplay());
        $array = [
            $user->getResourceNode()->getId() => $user->getResourceNode()->getSlug(),
            $resourceNode->getId() => $resourceNode->getSlug(),
        ];

        $this->assertSame($array, $resourceNode->getPathForDisplayToArray());

        $path = $user->getResourceNode()->getSlug().'/'.$resourceNode->getSlug();
        $this->assertSame($path, $resourceNode->getPathForDisplayRemoveBase(''));

        $this->assertSame('test', $resourceNode->getSlug());
        $this->assertFalse($resourceNode->isResourceFileAnImage());
        $this->assertFalse($resourceNode->isResourceFileAVideo());
        $this->assertNotEmpty(1, $resourceNode->getIcon());

        $router = $this->getContainer()->get(RouterInterface::class);
        $this->assertSame('<i class="fa fa-folder fa-3x"></i>', $resourceNode->getThumbnail($router));

        $this->assertSame($defaultCount + 1, $repo->count([]));
    }

    public function testCreateWithComment(): void
    {
        $em = $this->getEntityManager();

        $repoType = $em->getRepository(ResourceType::class);
        $repoComment = $em->getRepository(ResourceComment::class);

        $user = $this->createUser('julio');

        $type = $repoType->findOneBy(['name' => 'illustrations']);
        $resourceNode = (new ResourceNode())
            ->setContent('test')
            ->setTitle('test')
            ->setSlug('test')
            ->setResourceType($type)
            ->setCreator($user)
            ->setParent($user->getResourceNode())
        ;

        $em->persist($resourceNode);

        $comment = (new ResourceComment())
            ->setContent('content')
            ->setAuthor($user)
            ->setParent(null)
            ->setCreatedAt(new DateTime())
            ->setResourceNode($resourceNode)
            ->setUpdatedAt(new DateTime())
        ;
        $em->persist($comment);
        $resourceNode->addComment($comment);
        $em->flush();

        $this->assertSame(0, $comment->getChildren()->count());

        $comment2 = (new ResourceComment())
            ->setContent('content2')
            ->setAuthor($user)
            ->setResourceNode($resourceNode)
        ;
        $collection = new ArrayCollection();
        $collection->add($comment2);
        $comment->setChildren($collection);

        $em->persist($comment2);
        $em->persist($comment);
        $em->flush();

        /** @var ResourceComment $comment */
        $comment = $repoComment->find($comment->getId());

        $this->assertSame(1, $comment->getChildren()->count());
        $this->assertSame('content', $comment->getContent());
        $this->assertNotNull($comment->getId());
        $this->assertNotNull($comment->getResourceNode());
        $this->assertSame(1, $resourceNode->getComments()->count());
    }

    public function testCreateWithResourceLink(): void
    {
        $em = $this->getEntityManager();

        $repo = self::getContainer()->get(ResourceNodeRepository::class);
        $repoType = $em->getRepository(ResourceType::class);

        $user = $this->createUser('julio');
        $student = $this->createUser('student');
        $course = $this->createCourse('course');
        $session = $this->createSession('session');
        $group = $this->createGroup('group', $course);
        $userGroup = $this->createUserGroup('group');

        $type = $repoType->findOneBy(['name' => 'illustrations']);
        $resourceNode = (new ResourceNode())
            ->setContent('test')
            ->setTitle('test')
            ->setSlug('test')
            ->setResourceType($type)
            ->setCreator($user)
            ->setParent($user->getResourceNode())
        ;
        $em->persist($resourceNode);

        $link = (new ResourceLink())
            ->setVisibility(ResourceLink::VISIBILITY_PUBLISHED)
            ->setResourceNode($resourceNode)
            ->setUser($student)
            ->setCourse($course)
            ->setSession($session)
            ->setGroup($group)
            ->setUserGroup($userGroup)
            ->setCreatedAt(new DateTime())
            ->setUpdatedAt(new DateTime())
            ->setStartVisibilityAt(new DateTime())
            ->setEndVisibilityAt(new DateTime())
        ;
        $em->persist($link);
        $em->flush();
        $em->clear();

        /** @var ResourceNode $resourceNode */
        $resourceNode = $repo->find($resourceNode->getId());

        $this->assertSame(1, $resourceNode->getResourceLinks()->count());
        /** @var ResourceLink $link */
        $link = $resourceNode->getResourceLinks()->first();

        $this->assertNotNull($link->getStartVisibilityAt());
        $this->assertNotNull($link->getEndVisibilityAt());
        $this->assertTrue($link->hasSession());
        $this->assertTrue($link->hasCourse());
        $this->assertTrue($link->hasUser());
        $this->assertTrue($link->hasGroup());

        $this->assertTrue($link->isPublished());
        $this->assertFalse($link->isPending());
        $this->assertFalse($link->isDraft());
    }

    public function testGetResourceNodeFileContent(): void
    {
        $em = $this->getEntityManager();
        $repo = self::getContainer()->get(ResourceNodeRepository::class);

        $repoType = $em->getRepository(ResourceType::class);
        $user = $this->createUser('julio');

        $type = $repoType->findOneBy(['name' => 'illustrations']);

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
            ->setName($uploadedFile->getFilename())
            ->setOriginalName($uploadedFile->getFilename())
            ->setFile($uploadedFile)
            ->setDescription('desc')
            ->setCrop('')
            ->setMetadata([])
        ;
        $em->persist($resourceFile);

        $resourceNode->setContent('')->setResourceFile($resourceFile);
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
