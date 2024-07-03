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

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Xabbuh\XApi\Common\Exception\NotFoundException;
use Xabbuh\XApi\Model\Statement;
use Xabbuh\XApi\Model\StatementId;
use XApi\Repository\Api\StatementRepositoryInterface;

/**
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 */
final class StatementPutController
{
    private $repository;

    public function __construct(StatementRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function putStatement(Request $request, Statement $statement): Response
    {
        if (null === $statementId = $request->query->get('statementId')) {
            throw new BadRequestHttpException('Required statementId parameter is missing.');
        }

        try {
            $id = StatementId::fromString($statementId);
        } catch (\InvalidArgumentException $e) {
            throw new BadRequestHttpException(sprintf('Parameter statementId ("%s") is not a valid UUID.', $statementId), $e);
        }

        if (null !== $statement->getId() && !$id->equals($statement->getId())) {
            throw new ConflictHttpException(sprintf('Id parameter ("%s") and statement id ("%s") do not match.', $id->getValue(), $statement->getId()->getValue()));
        }

        try {
            $existingStatement = $this->repository->findStatementById($id);

            if (!$existingStatement->equals($statement)) {
                throw new ConflictHttpException('The new statement is not equal to an existing statement with the same id.');
            }
        } catch (NotFoundException $e) {
            $statement = $statement->withId($id);

            $this->repository->storeStatement($statement, true);
        }

        return new Response('', 204);
    }
}
