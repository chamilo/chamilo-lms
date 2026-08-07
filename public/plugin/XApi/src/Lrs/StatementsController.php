<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\Lrs;

use Chamilo\CoreBundle\Entity\XApiStatement;
use Chamilo\CoreBundle\Repository\XApiStatementRepository;
use Database;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;

/**
 * Class StatementsController.
 *
 * Implements the xAPI 1.0.3 statements resource on top of the XApi* core
 * entities. Statements are persisted, so they survive the PHP session and are
 * readable by the reporting pages and by any other client of the LRS.
 */
class StatementsController extends BaseController
{
    /**
     * Upper bound for the "limit" parameter, to keep a single request from
     * loading the whole statement table.
     */
    private const MAX_LIMIT = 1000;

    private const DEFAULT_LIMIT = 100;

    private EntityManagerInterface $em;

    private StatementMapper $mapper;

    public function __construct(Request $httpRequest)
    {
        parent::__construct($httpRequest);

        $this->em = Database::getManager();
        $this->mapper = new StatementMapper();
    }

    public function get(): Response
    {
        $query = $this->httpRequest->query;
        $repository = $this->getRepository();

        $statementId = trim((string) $query->get('statementId', ''));
        $voidedStatementId = trim((string) $query->get('voidedStatementId', ''));

        if ('' !== $statementId && '' !== $voidedStatementId) {
            return new JsonResponse(
                ['error' => 'The "statementId" and "voidedStatementId" parameters are mutually exclusive.'],
                Response::HTTP_BAD_REQUEST
            );
        }

        if ('' !== $statementId || '' !== $voidedStatementId) {
            $statement = '' !== $statementId
                ? $repository->findActiveById($statementId)
                : $repository->findVoidedById($voidedStatementId);

            if (null === $statement) {
                return new Response('', Response::HTTP_NOT_FOUND);
            }

            return new JsonResponse($this->mapper->toArray($statement), Response::HTTP_OK);
        }

        try {
            $filters = $this->buildFilters();
        } catch (RuntimeException $exception) {
            return new JsonResponse(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        $statements = $repository->findByFilters($filters);

        return new JsonResponse(
            [
                'statements' => array_map(
                    fn (XApiStatement $statement): array => $this->mapper->toArray($statement),
                    $statements
                ),
                'more' => $this->buildMoreUrl($filters, \count($statements), $repository),
            ],
            Response::HTTP_OK
        );
    }

    public function head(): Response
    {
        return $this->get()->setContent('');
    }

    public function put(): Response
    {
        $statementId = trim((string) $this->httpRequest->query->get('statementId', ''));

        if ('' === $statementId) {
            return new JsonResponse(
                ['error' => 'PUT /statements requires a "statementId" parameter.'],
                Response::HTTP_BAD_REQUEST
            );
        }

        try {
            $data = $this->decodeSingleStatement($this->httpRequest->getContent());
        } catch (RuntimeException $exception) {
            return new JsonResponse(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        $bodyId = $this->extractStatementId($data);

        if ('' !== $bodyId && 0 !== strcasecmp($bodyId, $statementId)) {
            return new JsonResponse(
                ['error' => 'The statement id in the body does not match the "statementId" parameter.'],
                Response::HTTP_CONFLICT
            );
        }

        if (null !== $this->getRepository()->find($statementId)) {
            // The specification allows an identical PUT to be a no-op; any
            // other content for an existing id is a conflict.
            return new Response('', Response::HTTP_CONFLICT);
        }

        try {
            $this->persistStatement($data, $statementId);
        } catch (RuntimeException $exception) {
            return new JsonResponse(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return new Response('', Response::HTTP_NO_CONTENT);
    }

    public function post(): Response
    {
        try {
            $statements = $this->decodeStatementCollection($this->httpRequest->getContent());
        } catch (RuntimeException $exception) {
            return new JsonResponse(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        $repository = $this->getRepository();
        $storedIds = [];

        foreach ($statements as $data) {
            $statementId = $this->extractStatementId($data);

            if ('' === $statementId) {
                $statementId = Uuid::v4()->toRfc4122();
            } elseif (null !== $repository->find($statementId)) {
                return new JsonResponse(
                    ['error' => sprintf('A statement with id "%s" already exists.', $statementId)],
                    Response::HTTP_CONFLICT
                );
            }

            try {
                $this->persistStatement($data, $statementId, false);
            } catch (RuntimeException $exception) {
                return new JsonResponse(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
            }

            $storedIds[] = $statementId;
        }

        $this->em->flush();

        return new JsonResponse($storedIds, Response::HTTP_OK);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function persistStatement(array $data, string $statementId, bool $flush = true): void
    {
        $statement = $this->mapper->toEntity($data, $statementId);

        $this->em->persist($statement);

        if ($flush) {
            $this->em->flush();
        }
    }

    /**
     * Reads and validates the query parameters of GET /statements.
     *
     * @return array<string, mixed>
     */
    private function buildFilters(): array
    {
        $query = $this->httpRequest->query;

        $limit = $query->getInt('limit', self::DEFAULT_LIMIT);

        if ($limit < 0) {
            throw new RuntimeException('The "limit" parameter must not be negative.');
        }

        $filters = [
            'registration' => $this->trimmedOrNull($query->get('registration')),
            'verb' => $this->trimmedOrNull($query->get('verb')),
            'activity' => $this->trimmedOrNull($query->get('activity')),
            'since' => $this->toTimestamp($query->get('since'), 'since'),
            'until' => $this->toTimestamp($query->get('until'), 'until'),
            'ascending' => $query->getBoolean('ascending'),
            'limit' => min(0 === $limit ? self::MAX_LIMIT : $limit, self::MAX_LIMIT),
            'cursor' => max(0, $query->getInt('cursor')),
        ];

        $agent = $this->trimmedOrNull($query->get('agent'));

        if (null !== $agent) {
            $decoded = json_decode($agent, true);

            if (JSON_ERROR_NONE !== json_last_error() || !\is_array($decoded)) {
                throw new RuntimeException('The "agent" parameter must be a JSON object.');
            }

            $filters['agent'] = $decoded;
        }

        return $filters;
    }

    /**
     * Builds the paging URL when more statements match than were returned.
     *
     * @param array<string, mixed> $filters
     */
    private function buildMoreUrl(array $filters, int $returned, XApiStatementRepository $repository): string
    {
        $limit = (int) $filters['limit'];
        $cursor = (int) $filters['cursor'];

        if ($returned < $limit) {
            return '';
        }

        if ($repository->countByFilters($filters) <= $cursor + $returned) {
            return '';
        }

        $parameters = $this->httpRequest->query->all();
        $parameters['cursor'] = $cursor + $returned;
        $parameters['limit'] = $limit;

        return $this->httpRequest->getPathInfo().'?'.http_build_query($parameters, '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeSingleStatement(string $content): array
    {
        $decoded = $this->decodeJson($content);

        if ($this->isSequentialArray($decoded)) {
            throw new RuntimeException('PUT /statements expects a single statement object.');
        }

        return $decoded;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function decodeStatementCollection(string $content): array
    {
        $decoded = $this->decodeJson($content);

        if (!$this->isSequentialArray($decoded)) {
            return [$decoded];
        }

        $statements = [];

        foreach ($decoded as $statement) {
            if (!\is_array($statement)) {
                throw new RuntimeException('Invalid statement entry in collection.');
            }

            $statements[] = $statement;
        }

        return $statements;
    }

    /**
     * @return array<mixed>
     */
    private function decodeJson(string $content): array
    {
        $content = trim($content);

        if ('' === $content) {
            throw new RuntimeException('Statement payload is empty.');
        }

        $decoded = json_decode($content, true);

        if (JSON_ERROR_NONE !== json_last_error() || !\is_array($decoded)) {
            throw new RuntimeException('Invalid statement JSON payload.');
        }

        return $decoded;
    }

    /**
     * @param array<string, mixed> $statement
     */
    private function extractStatementId(array $statement): string
    {
        $statementId = $statement['id'] ?? '';

        return \is_string($statementId) ? trim($statementId) : '';
    }

    private function getRepository(): XApiStatementRepository
    {
        /** @var XApiStatementRepository $repository */
        $repository = $this->em->getRepository(XApiStatement::class);

        return $repository;
    }

    private function trimmedOrNull(mixed $value): ?string
    {
        if (!\is_string($value)) {
            return null;
        }

        $value = trim($value);

        return '' === $value ? null : $value;
    }

    private function toTimestamp(mixed $value, string $parameter): ?int
    {
        $value = $this->trimmedOrNull($value);

        if (null === $value) {
            return null;
        }

        $date = date_create_immutable($value);

        if (false === $date) {
            throw new RuntimeException(sprintf('The "%s" parameter is not a valid ISO 8601 date.', $parameter));
        }

        return $date->getTimestamp();
    }

    /**
     * @param array<mixed> $array
     */
    private function isSequentialArray(array $array): bool
    {
        return array_keys($array) === range(0, \count($array) - 1);
    }
}
