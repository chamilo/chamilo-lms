<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\XApiStatement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<XApiStatement>
 *
 * @method XApiStatement|null find($id, $lockMode = null, $lockVersion = null)
 * @method XApiStatement|null findOneBy(array $criteria, array $orderBy = null)
 * @method XApiStatement[]    findAll()
 * @method XApiStatement[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class XApiStatementRepository extends ServiceEntityRepository
{
    public const VOIDED_VERB_ID = 'http://adlnet.gov/expapi/verbs/voided';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, XApiStatement::class);
    }

    /**
     * Finds a statement that has not been voided by a later voiding statement.
     */
    public function findActiveById(string $id): ?XApiStatement
    {
        $statement = $this->find($id);

        if (null === $statement || $this->isVoided($id)) {
            return null;
        }

        return $statement;
    }

    /**
     * Finds a statement only if a voiding statement targets it.
     */
    public function findVoidedById(string $id): ?XApiStatement
    {
        if (!$this->isVoided($id)) {
            return null;
        }

        return $this->find($id);
    }

    public function isVoided(string $id): bool
    {
        $count = (int) $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->innerJoin('s.verb', 'v')
            ->innerJoin('s.object', 'o')
            ->andWhere('v.id = :voidedVerb')
            ->andWhere('o.referencedStatementId = :id')
            ->setParameter('voidedVerb', self::VOIDED_VERB_ID)
            ->setParameter('id', $id)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return $count > 0;
    }

    /**
     * Applies the xAPI 1.0.3 GET /statements filters.
     *
     * Supported keys: registration, verb, activity, agent (an array with the
     * inverse functional identifiers), since, until, ascending, limit, cursor.
     *
     * @param array<string, mixed> $filters
     *
     * @return array<int, XApiStatement>
     */
    public function findByFilters(array $filters): array
    {
        $qb = $this->buildFilteredQuery($filters);

        $limit = $filters['limit'] ?? null;
        $cursor = $filters['cursor'] ?? null;

        if (null !== $limit && $limit > 0) {
            $qb->setMaxResults($limit);
        }

        if (null !== $cursor && $cursor > 0) {
            $qb->setFirstResult($cursor);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Counts the statements matching the filters, ignoring limit and cursor.
     *
     * @param array<string, mixed> $filters
     */
    public function countByFilters(array $filters): int
    {
        $qb = $this->buildFilteredQuery($filters);
        $qb->select('COUNT(DISTINCT s.id)')->resetDQLPart('orderBy');

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function buildFilteredQuery(array $filters): QueryBuilder
    {
        $qb = $this->createQueryBuilder('s');

        // A voided statement is no longer returned by the regular query.
        $voided = $this->createQueryBuilder('vs')
            ->select('1')
            ->innerJoin('vs.verb', 'vv')
            ->innerJoin('vs.object', 'vo')
            ->andWhere('vv.id = :voidedVerb')
            ->andWhere('vo.referencedStatementId = s.id')
            ->getDQL()
        ;

        $qb
            ->andWhere($qb->expr()->not($qb->expr()->exists($voided)))
            ->setParameter('voidedVerb', self::VOIDED_VERB_ID)
        ;

        if (!empty($filters['registration'])) {
            $qb
                ->innerJoin('s.context', 'c')
                ->andWhere('c.registration = :registration')
                ->setParameter('registration', $filters['registration'])
            ;
        }

        if (!empty($filters['verb'])) {
            $qb
                ->innerJoin('s.verb', 'verb')
                ->andWhere('verb.id = :verbId')
                ->setParameter('verbId', $filters['verb'])
            ;
        }

        if (!empty($filters['activity'])) {
            $qb
                ->innerJoin('s.object', 'object')
                ->andWhere('object.activityId = :activityId')
                ->setParameter('activityId', $filters['activity'])
            ;
        }

        if (!empty($filters['agent']) && \is_array($filters['agent'])) {
            $this->applyAgentFilter($qb, $filters['agent']);
        }

        if (!empty($filters['since'])) {
            $qb
                ->andWhere('s.stored > :since')
                ->setParameter('since', $filters['since'])
            ;
        }

        if (!empty($filters['until'])) {
            $qb
                ->andWhere('s.stored <= :until')
                ->setParameter('until', $filters['until'])
            ;
        }

        $qb->orderBy('s.stored', !empty($filters['ascending']) ? 'ASC' : 'DESC');

        return $qb;
    }

    /**
     * Matches the actor by any of the four inverse functional identifiers.
     *
     * @param array<string, mixed> $agent
     */
    private function applyAgentFilter(QueryBuilder $qb, array $agent): void
    {
        $qb->innerJoin('s.actor', 'actor');

        if (!empty($agent['mbox'])) {
            $qb
                ->andWhere('actor.mbox = :agentMbox')
                ->setParameter('agentMbox', (string) $agent['mbox'])
            ;

            return;
        }

        if (!empty($agent['mbox_sha1sum'])) {
            $qb
                ->andWhere('actor.mboxSha1Sum = :agentSha1')
                ->setParameter('agentSha1', (string) $agent['mbox_sha1sum'])
            ;

            return;
        }

        if (!empty($agent['openid'])) {
            $qb
                ->andWhere('actor.openId = :agentOpenId')
                ->setParameter('agentOpenId', (string) $agent['openid'])
            ;

            return;
        }

        if (!empty($agent['account']['name'])) {
            $qb
                ->andWhere('actor.accountName = :agentAccountName')
                ->setParameter('agentAccountName', (string) $agent['account']['name'])
            ;

            if (!empty($agent['account']['homePage'])) {
                $qb
                    ->andWhere('actor.accountHomePage = :agentAccountHomePage')
                    ->setParameter('agentAccountHomePage', (string) $agent['account']['homePage'])
                ;
            }

            return;
        }

        // An agent filter without any identifier must not match every statement.
        $qb->andWhere('1 = 0');
    }
}
