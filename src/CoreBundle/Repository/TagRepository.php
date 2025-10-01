<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\PortfolioRelTag;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\Tag;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\UserRelTag;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

class TagRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tag::class);
    }

    /**
     * @return Collection|Tag[]
     */
    public function findTagsByField(string $tag, ExtraField $field)
    {
        $qb = $this->createQueryBuilder('t')
            ->where('t.tag LIKE :tag')
            ->andWhere('t.field = :field')
            ->setParameter('field', $field)
            ->setParameter('tag', "$tag%")
        ;

        return $qb->getQuery()->getResult();
    }

    /**
     * @return Collection|Tag[]
     */
    public function getTagsByUser(ExtraField $field, User $user)
    {
        $qb = $this->createQueryBuilder('t')
            ->innerJoin('t.userRelTags', 'ut')
            ->where('t.field = :field')
            ->andWhere('ut.user = :user')
            ->setParameter('field', $field)
            ->setParameter('user', $user)
        ;

        return $qb->getQuery()->getResult();
    }

    /**
     * Get the tags for an item.
     *
     * @return Collection|Tag[]
     */
    public function getTagsByItem(ExtraField $extraField, int $itemId)
    {
        $qb = $this->createQueryBuilder('t')
            ->innerJoin('t.extraFieldRelTags', 'et')
            ->where('et.itemId = :itemId')
            ->andWhere('et.field = :field')
            ->setParameter('field', $extraField)
            ->setParameter('itemId', $itemId)
        ;

        return $qb->getQuery()->getResult();
    }

    public function addTagToUser(ExtraField $extraField, User $user, string $tag): User
    {
        $entityTag = $this->findOneBy(['tag' => $tag, 'field' => $extraField]);
        $em = $this->getEntityManager();
        if (null === $entityTag) {
            $entityTag = (new Tag())
                ->setField($extraField)
                ->setTag($tag)
            ;
            $em->persist($entityTag);
        }

        $userRelTag = (new UserRelTag())
            ->setUser($user)
            ->setTag($entityTag)
        ;

        $exists = $user->getUserRelTags()->exists(
            function ($key, $element) use ($userRelTag) {
                return $userRelTag->getTag() === $element->getTag();
            }
        );

        if (!$exists) {
            $entityTag->setCount($entityTag->getCount() + 1);
            $em->persist($entityTag);
            $user->getUserRelTags()->add($userRelTag);
        }

        $em->persist($user);
        $em->flush();

        return $user;
    }

    public function deleteTagFromUser(User $user, Tag $tag): bool
    {
        $em = $this->getEntityManager();

        $userRelTags = $user->getUserRelTags()->filter(
            function ($element) use ($tag) {
                return $tag->getId() === $element->getTag()->getId();
            }
        );

        if ($userRelTags->count() > 0 && $userRelTags->first()) {
            $tag->setCount($tag->getCount() - 1);
            $em->persist($tag);
            $em->remove($userRelTags->first());
            $em->flush();

            return true;
        }

        return false;
    }

    public function findForPortfolioInCourseQuery(Course $course, ?Session $session = null): QueryBuilder
    {
        $qb = $this
            ->createQueryBuilder('t')
            ->innerJoin(PortfolioRelTag::class, 'prt', Join::WITH, 't = prt.tag')
            ->where('prt.course = :course')
            ->setParameter('course', $course)
        ;

        if ($session) {
            $qb
                ->andWhere('prt.session = :session')
                ->setParameter('session', $session)
            ;
        } else {
            $qb->andWhere('prt.session IS NULL');
        }

        return $qb;
    }
}
