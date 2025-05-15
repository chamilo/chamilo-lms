<?php

declare(strict_types=1);

/*
 * This file is part of the xAPI package.
 *
 * (c) Christian Flothmann <christian.flothmann@xabbuh.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace XApi\Repository\ORM;

use Doctrine\ORM\EntityRepository;
use XApi\Repository\Doctrine\Mapping\Context;
use XApi\Repository\Doctrine\Mapping\Statement;
use XApi\Repository\Doctrine\Repository\Mapping\StatementRepository as BaseStatementRepository;

/**
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 */
final class StatementRepository extends EntityRepository implements BaseStatementRepository
{
    public function findStatement(array $criteria)
    {
        return parent::findOneBy($criteria);
    }

    public function findStatements(array $criteria)
    {
        if (!empty($criteria['registration'])) {
            $contexts = $this->_em->getRepository(Context::class)->findBy([
                'registration' => $criteria['registration'],
            ]);

            $criteria['context'] = $contexts;
        }

        if (!empty($criteria['verb'])) {
            $verbs = $this->_em->getRepository(Verb::class)->findBy(['id' => $criteria['verb']]);

            $criteria['verb'] = $verbs;
        }

        unset(
            $criteria['registration'],
            $criteria['related_activities'],
            $criteria['related_agents'],
            $criteria['ascending'],
        );

        return parent::findBy(
            $criteria,
            ['created' => 'ASC'],
            $criteria['limit'] ?? null,
            $criteria['cursor'] ?? null
        );
    }

    public function storeStatement(Statement $mappedStatement, $flush = true): void
    {
        $this->_em->persist($mappedStatement);

        if ($flush) {
            $this->_em->flush();
        }
    }
}
