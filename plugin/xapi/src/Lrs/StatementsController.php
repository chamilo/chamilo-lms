<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\Lrs;

use Chamilo\PluginBundle\Entity\XApi\Lrs\Statement as StatementEntity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Xabbuh\XApi\Model\Statement;
use Xabbuh\XApi\Model\StatementId;
use Xabbuh\XApi\Serializer\Symfony\Serializer;

/**
 * Class StatementsController.
 *
 * @package Chamilo\PluginBundle\XApi\Lrs
 */
class StatementsController extends BaseController
{
    /**
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function put()
    {
        $request = $this->httpRequest;

        if (null === $request->query->get('statementId')) {
            throw new BadRequestHttpException('Required statementId parameter is missing.');
        }

        $statementId = $request->query->get('statementId');
        $id = StatementId::fromString($statementId);

        $statement = $this->deserializeStatement(
            $this->httpRequest->getContent()
        );

        if (null !== $statement->getId() && !$id->equals($statement->getId())) {
            throw new ConflictHttpException(
                "Id parameter ({$id->getValue()}) and statement id ({$statement->getId()->getValue()}) do not match."
            );
        }

        $em = \Database::getManager();

        $existingStatement = $em->find(StatementEntity::class, $id->getValue());

        if ($existingStatement && !$existingStatement->equals($statement)) {
            throw new ConflictHttpException('The new statement is not equal to an existing statement with the same id.');
        }

        $em->persist(StatementEntity::fromModel($statement));
        $em->flush();

        return JsonResponse::create(
            null,
            Response::HTTP_NO_CONTENT
        );
    }

    /**
     * @param string $content
     *
     * @return \Xabbuh\XApi\Model\Statement
     */
    private function deserializeStatement($content)
    {
        $serializer = Serializer::createSerializer();

        return $serializer->deserialize($content, Statement::class, 'json');
    }
}
