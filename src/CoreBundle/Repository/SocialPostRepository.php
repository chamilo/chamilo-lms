<?php

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\SocialPost;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

class SocialPostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SocialPost::class);
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function update(SocialPost $post)
    {
        $em = $this->getEntityManager();
        $em->persist($post);
        $em->flush();
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function delete(SocialPost $post)
    {
        $em = $this->getEntityManager();
        $em->remove($post);
        $em->flush();
    }
}
