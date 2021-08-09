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
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Xabbuh\XApi\Common\Exception\NotFoundException;
use Xabbuh\XApi\Model\Statement;
use Xabbuh\XApi\Model\StatementId;
use Xabbuh\XApi\Model\StatementResult;
use Xabbuh\XApi\Serializer\StatementResultSerializerInterface;
use Xabbuh\XApi\Serializer\StatementSerializerInterface;
use XApi\LrsBundle\Model\StatementsFilterFactory;
use XApi\LrsBundle\Response\AttachmentResponse;
use XApi\LrsBundle\Response\MultipartResponse;
use XApi\Repository\Api\StatementRepositoryInterface;

/**
 * @author Jérôme Parmentier <jerome.parmentier@acensi.fr>
 */
final class StatementGetController
{
    private static $getParameters = [
        'statementId' => true,
        'voidedStatementId' => true,
        'agent' => true,
        'verb' => true,
        'activity' => true,
        'registration' => true,
        'related_activities' => true,
        'related_agents' => true,
        'since' => true,
        'until' => true,
        'limit' => true,
        'format' => true,
        'attachments' => true,
        'ascending' => true,
    ];

    private $repository;
    private $statementSerializer;
    private $statementResultSerializer;
    private $statementsFilterFactory;

    public function __construct(StatementRepositoryInterface $repository, StatementSerializerInterface $statementSerializer, StatementResultSerializerInterface $statementResultSerializer, StatementsFilterFactory $statementsFilterFactory)
    {
        $this->repository = $repository;
        $this->statementSerializer = $statementSerializer;
        $this->statementResultSerializer = $statementResultSerializer;
        $this->statementsFilterFactory = $statementsFilterFactory;
    }

    /**
     * @throws BadRequestHttpException if the query parameters does not comply with xAPI specification
     *
     * @return Response
     */
    public function getStatement(Request $request)
    {
        $query = new ParameterBag(\array_intersect_key($request->query->all(), self::$getParameters));

        $this->validate($query);

        $includeAttachments = $query->filter('attachments', false, FILTER_VALIDATE_BOOLEAN);
        try {
            if (($statementId = $query->get('statementId')) !== null) {
                $statement = $this->repository->findStatementById(StatementId::fromString($statementId));

                $response = $this->buildSingleStatementResponse($statement, $includeAttachments);
            } elseif (($voidedStatementId = $query->get('voidedStatementId')) !== null) {
                $statement = $this->repository->findVoidedStatementById(StatementId::fromString($voidedStatementId));

                $response = $this->buildSingleStatementResponse($statement, $includeAttachments);
            } else {
                $statements = $this->repository->findStatementsBy($this->statementsFilterFactory->createFromParameterBag($query));

                $response = $this->buildMultiStatementsResponse($statements, $includeAttachments);
            }
        } catch (NotFoundException $e) {
            $response = $this->buildMultiStatementsResponse([]);
        }

        $now = new \DateTime();
        $response->headers->set('X-Experience-API-Consistent-Through', $now->format(\DateTime::ATOM));

        return $response;
    }

    /**
     * @param bool $includeAttachments true to include the attachments in the response, false otherwise
     *
     * @return JsonResponse|MultipartResponse
     */
    private function buildSingleStatementResponse(Statement $statement, $includeAttachments = false)
    {
        $json = $this->statementSerializer->serializeStatement($statement);

        $response = new JsonResponse($json, 200, [], true);

        if ($includeAttachments) {
            $response = $this->buildMultipartResponse($response, [$statement]);
        }

        $response->setLastModified($statement->getStored());

        return $response;
    }

    /**
     * @param Statement[] $statements
     * @param bool        $includeAttachments true to include the attachments in the response, false otherwise
     *
     * @return JsonResponse|MultipartResponse
     */
    private function buildMultiStatementsResponse(array $statements, $includeAttachments = false)
    {
        $json = $this->statementResultSerializer->serializeStatementResult(new StatementResult($statements));

        $response = new JsonResponse($json, 200, [], true);

        if ($includeAttachments) {
            $response = $this->buildMultipartResponse($response, $statements);
        }

        return $response;
    }

    /**
     * @param Statement[] $statements
     *
     * @return MultipartResponse
     */
    private function buildMultipartResponse(JsonResponse $statementResponse, array $statements)
    {
        $attachmentsParts = [];

        foreach ($statements as $statement) {
            foreach ((array) $statement->getAttachments() as $attachment) {
                $attachmentsParts[] = new AttachmentResponse($attachment);
            }
        }

        return new MultipartResponse($statementResponse, $attachmentsParts);
    }

    /**
     * Validate the parameters.
     *
     * @throws BadRequestHttpException if the parameters does not comply with the xAPI specification
     */
    private function validate(ParameterBag $query)
    {
        $hasStatementId = $query->has('statementId');
        $hasVoidedStatementId = $query->has('voidedStatementId');

        if ($hasStatementId && $hasVoidedStatementId) {
            throw new BadRequestHttpException('Request must not have both statementId and voidedStatementId parameters at the same time.');
        }

        $hasAttachments = $query->has('attachments');
        $hasFormat = $query->has('format');
        $queryCount = $query->count();

        if (($hasStatementId || $hasVoidedStatementId) && $hasAttachments && $hasFormat && $queryCount > 3) {
            throw new BadRequestHttpException('Request must not contain statementId or voidedStatementId parameters, and also any other parameter besides "attachments" or "format".');
        }

        if (($hasStatementId || $hasVoidedStatementId) && ($hasAttachments || $hasFormat) && $queryCount > 2) {
            throw new BadRequestHttpException('Request must not contain statementId or voidedStatementId parameters, and also any other parameter besides "attachments" or "format".');
        }

        if (($hasStatementId || $hasVoidedStatementId) && $queryCount > 1) {
            throw new BadRequestHttpException('Request must not contain statementId or voidedStatementId parameters, and also any other parameter besides "attachments" or "format".');
        }
    }
}
