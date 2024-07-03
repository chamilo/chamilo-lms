<?php

/*
 * This file is part of the xAPI package.
 *
 * (c) Christian Flothmann <christian.flothmann@xabbuh.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace XApi\LrsBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Xabbuh\XApi\Common\Exception\NotFoundException;
use Xabbuh\XApi\Model\Statement;
use XApi\Repository\Api\StatementRepositoryInterface;

/**
 * @author Jérôme Parmentier <jerome.parmentier@acensi.fr>
 */
final class StatementPostController
{
    /**
     * @var StatementRepositoryInterface
     */
    private $repository;

    public function __construct(StatementRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function postStatements(Request $request, array $statements): JsonResponse
    {
        $statementsToStore = [];

        /** @var Statement $statement */
        foreach ($statements as $statement) {
            if (null === $statementId = $statement->getId()) {
                $statementsToStore[] = $statement;

                continue;
            }

            try {
                $existingStatement = $this->repository->findStatementById($statement->getId());

                if (!$existingStatement->equals($statement)) {
                    throw new ConflictHttpException('The new statement is not equal to an existing statement with the same id.');
                }
            } catch (NotFoundException $e) {
                $statementsToStore[] = $statement;
            }
        }

        $uuids = [];

        foreach ($statementsToStore as $statement) {
            $uuids[] = $this->repository->storeStatement($statement, true)->getValue();
        }

        return new JsonResponse($uuids);
    }
}
