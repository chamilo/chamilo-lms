<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\SocialPost;
use Chamilo\CoreBundle\Entity\SocialPostFeedback;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CoreBundle\Repository\SocialPostRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use DateTime;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\Mapping\MappingException;

class SocialPostRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     * @throws MappingException
     */
    public function testCreateMessageWithFeedback(): void
    {
        $em = $this->getEntityManager();

        $socialPostRepo = self::getContainer()
            ->get(SocialPostRepository::class)
        ;
        $socialPostFeedbackRepo = $em->getRepository(SocialPostFeedback::class);

        $admin = $this->getUser('admin');
        $testUser = $this->createUser('test');

        $post = (new SocialPost())
            ->setContent('content')
            ->setSender($admin)
            ->setUserReceiver($testUser)
        ;
        $socialPostRepo->update($post);

        // 1. Message exists in the inbox.
        $this->assertSame(1, $socialPostRepo->count([]));

        $feedback = (new SocialPostFeedback())
            ->setSocialPost($post)
            ->setUser($testUser)
            ->setUpdatedAt(new DateTime())
            ->setDisliked(true)
            ->setLiked(true)
        ;
        $em->persist($feedback);
        $em->flush();
        $em->clear();

        $this->assertSame(1, $socialPostFeedbackRepo->count([]));
        $this->assertNotNull($feedback->getUser());
        $this->assertNotNull($feedback->getUpdatedAt());
        $this->assertNotNull($feedback->getSocialPost());

        /** @var SocialPost $post */
        $post = $socialPostRepo->find($post->getId());
        $this->assertSame(
            1,
            $post->getCountFeedbackLikes()
        );
        $this->assertSame(
            1,
            $post->getCountFeedbackDislikes()
        );

        $socialPostRepo->delete($post);

        $this->assertSame(0, $socialPostRepo->count([]));
        $this->assertSame(0, $socialPostFeedbackRepo->count([]));
    }

    public function testPostToOwnWall(): void
    {
        $student = $this->createUser('student');

        $studentToken = $this->getUserTokenFromUser($student);
        $studentIri = $this->findIriBy(User::class, ['username' => $student->getUsername()]);

        $client = $this->createClientWithCredentials($studentToken);

        $client->request(
            'POST',
            '/api/social_posts',
            [
                'json' => [
                    'content' => 'Hello world',
                    'type' => SocialPost::TYPE_WALL_POST,
                    'sender' => $studentIri,
                ],
            ]
        );

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => '/api/contexts/SocialPost',
            '@type' => 'SocialPost',
            'content' => 'Hello world',
            'sender' => [
                '@id' => $studentIri,
                'username' => $student->getUsername(),
            ],
            'userReceiver' => null,
            'groupReceiver' => null,
        ]);

        $response = $client->request(
            'GET',
            '/api/social_posts',
            [
                'query' => ['socialwall_wallOwner' => $student->getId()],
            ]
        );

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => '/api/contexts/SocialPost',
            '@id' => '/api/social_posts',
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => 1,
            'hydra:view' => [
                '@id' => '/api/social_posts?socialwall_wallOwner='.$student->getId(),
                '@type' => 'hydra:PartialCollectionView',
            ],
        ]);
        $this->assertCount(1, $response->toArray()['hydra:member']);
    }

    public function testPostToFriendsWall(): void
    {
        $userRepo = self::getContainer()->get(UserRepository::class);

        $student2 = $this->createUser('student2');

        $student1 = $this->createUser('student1');
        $student1->addFriend($student2);

        $userRepo->update($student1);

        $student1Iri = $this->findIriBy(User::class, ['username' => $student1->getUsername()]);
        $student2Iri = $this->findIriBy(User::class, ['username' => $student2->getUsername()]);

        $clientForStudent1 = $this->createClientWithCredentials(
            $this->getUserTokenFromUser($student1)
        );

        // student1 posts in their wall
        $clientForStudent1->request(
            'POST',
            '/api/social_posts',
            [
                'json' => [
                    'content' => 'Hello world',
                    'type' => SocialPost::TYPE_WALL_POST,
                    'sender' => $student1Iri,
                ],
            ]
        );

        // student1 posts in student2's wall
        $clientForStudent1->request(
            'POST',
            '/api/social_posts',
            [
                'json' => [
                    'content' => 'Hello friend',
                    'type' => SocialPost::TYPE_WALL_POST,
                    'sender' => $student1Iri,
                    'userReceiver' => $student2Iri,
                ],
            ]
        );

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => '/api/contexts/SocialPost',
            '@type' => 'SocialPost',
            'content' => 'Hello friend',
            'sender' => [
                '@id' => $student1Iri,
                'username' => $student1->getUsername(),
            ],
            'userReceiver' => [
                '@id' => $student2Iri,
                'username' => $student2->getUsername(),
            ],
            'groupReceiver' => null,
        ]);

        // student1 views student2's wall
        $response = $clientForStudent1->request(
            'GET',
            sprintf('/api/social_posts?socialwall_wallOwner=%d', $student2->getId())
        );

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => '/api/contexts/SocialPost',
            '@id' => '/api/social_posts',
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => 1,
            'hydra:view' => [
                '@id' => '/api/social_posts?socialwall_wallOwner='.$student2->getId(),
                '@type' => 'hydra:PartialCollectionView',
            ],
        ]);
        $this->assertCount(1, $response->toArray()['hydra:member']);
    }
}
