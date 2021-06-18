<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelUser;
use Chamilo\CoreBundle\Entity\SysAnnouncement;
use Chamilo\CoreBundle\Entity\User;
use Datetime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Security\Core\Security;

class SysAnnouncementRepository extends ServiceEntityRepository
{
    protected ParameterBagInterface $parameterBag;
    protected Security $security;

    public function __construct(ManagerRegistry $registry, ParameterBagInterface $parameterBag, Security $security)
    {
        parent::__construct($registry, SysAnnouncement::class);
        $this->parameterBag = $parameterBag;
        $this->security = $security;
    }

    public function getVisibilityList()
    {
        $hierarchy = $this->parameterBag->get('security.role_hierarchy.roles');
        $roles = [];
        array_walk_recursive($hierarchy, function ($role) use (&$roles): void {
            $roles[$role] = $role;
        });

        return $roles;
    }

    public function getAnnouncementsQueryBuilder(string $iso, AccessUrl $url, ?User $user = null): QueryBuilder
    {
        $qb = $this->createQueryBuilder('s');
        $qb
            ->andWhere('s.lang IS NULL OR s.lang = :lang OR s.lang = :empty')
            ->andWhere('s.url = :url')
            ->setParameters(
                ['url' => $url, 'lang' => $iso, 'empty' => '']
            )
        ;

        $this->addDateQueryBuilder($qb);

        if (null !== $user) {
            $this->addRoleListQueryBuilder($user->getRoles(), $qb);
        }

        $qb->orderBy('s.dateStart', 'DESC');

        return $qb;
    }

    public function getAnnouncements(User $user, AccessUrl $url, string $iso): array
    {
        $qb = $this->getAnnouncementsQueryBuilder($iso, $url, $user);

        $announcements = $qb->getQuery()->getResult();

        $cutSize = 500;
        $list = [];
        if (!empty($announcements)) {
            /** @var SysAnnouncement $announcement */
            foreach ($announcements as $announcement) {
                if ($announcement->hasCareer()) {
                    $promotionList = [];
                    if ($announcement->hasPromotion()) {
                        $promotionList[] = $announcement->getPromotion();
                    } else {
                        $promotionList = $announcement->getCareer()->getPromotions();
                    }

                    $show = false;
                    foreach ($promotionList as $promotion) {
                        $sessionList = $promotion->getSessions();
                        foreach ($sessionList as $session) {
                            $subscription = (new SessionRelUser())
                                ->setUser($user)
                                ->setSession($session)
                                ->setRelationType(0)
                            ;

                            // Check student
                            if ($this->security->isGranted('ROLE_STUDENT') &&
                                $session->hasUser($subscription)
                                //\SessionManager::isUserSubscribedAsStudent($sessionId, $userId)
                            ) {
                                $show = true;

                                break 2;
                            }

                            if ($this->security->isGranted('ROLE_TEACHER') &&
                                $session->isUserGeneralCoach($user)
                                //SessionManager::user_is_general_coach($userId, $sessionId)
                            ) {
                                $show = true;

                                break 2;
                            }

                            // Check course coach
                            //$coaches = \SessionManager::getCoachesBySession($sessionId);
                            if ($this->security->isGranted('ROLE_TEACHER') &&
                                $session->getSessionRelCourseByUser($user, Session::COACH)->count() > 0
                            ) {
                                $show = true;

                                break 2;
                            }
                        }
                    }

                    if (false === $show) {
                        continue;
                    }
                }

                $announcementData = [
                    'id' => $announcement->getId(),
                    'title' => $announcement->getTitle(),
                    'content' => $announcement->getContent(),
                    'readMore' => null,
                ];

                if (api_strlen(strip_tags($announcement->getContent())) > $cutSize) {
                    $announcementData['content'] = cut($announcement->getContent(), $cutSize);
                    $announcementData['readMore'] = true;
                }
                $list[] = $announcementData;
            }
        }

        if (0 === \count($list)) {
            return [];
        }

        return $list;
    }

    public function addRoleListQueryBuilder(array $roles, QueryBuilder $qb = null): QueryBuilder
    {
        $qb = $this->getOrCreateQueryBuilder($qb);

        $conditions[] = $qb->expr()->like('s.roles', $qb->expr()->literal('%ROLE_ANONYMOUS%'));

        if (!empty($roles)) {
            foreach ($roles as $role) {
                $conditions[] = $qb->expr()->like('s.roles', $qb->expr()->literal('%'.$role.'%'));
            }
        }

        $orX = $qb->expr()->orX();
        $orX->addMultiple($conditions);
        $qb->andWhere($orX);

        return $qb;
    }

    public function addDateQueryBuilder(QueryBuilder $qb = null): QueryBuilder
    {
        $qb = $this->getOrCreateQueryBuilder($qb);
        $qb
            ->andWhere('s.dateStart <= :now AND s.dateEnd > :now')
            ->setParameter('now', new Datetime(), Types::DATETIME_MUTABLE)
        ;

        return $qb;
    }

    public function update(SysAnnouncement $sysAnnouncement, $andFlush = true): void
    {
        $this->getEntityManager()->persist($sysAnnouncement);
        if ($andFlush) {
            $this->getEntityManager()->flush();
        }
    }

    protected function getOrCreateQueryBuilder(QueryBuilder $qb = null, string $alias = 's'): QueryBuilder
    {
        return $qb ?: $this->createQueryBuilder($alias);
    }
}
