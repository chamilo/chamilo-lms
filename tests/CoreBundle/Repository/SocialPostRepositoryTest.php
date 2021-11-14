<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\SocialPost;
use Chamilo\CoreBundle\Entity\SocialPostFeedback;
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

        $post = $socialPostRepo->find($post->getId());
        $this->assertSame(
            1,
            $post->getLikes()
                ->count()
        );

        $socialPostRepo->delete($post);

        $this->assertSame(0, $socialPostRepo->count([]));
        $this->assertSame(0, $socialPostFeedbackRepo->count([]));
    }
}
