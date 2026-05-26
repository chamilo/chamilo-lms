<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\Lrs;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;

/**
 * Class StatementsController.
 */
class StatementsController extends BaseController
{
    public function __construct(Request $httpRequest)
    {
        parent::__construct($httpRequest);
    }

    public function get(): Response
    {
        $this->ensureSessionStarted();

        $statementId = trim((string) $this->httpRequest->query->get('statementId', ''));

        if ('' !== $statementId) {
            $statement = $_SESSION['xapi_statement_store'][$statementId] ?? null;

            if (!\is_array($statement)) {
                return new Response('', Response::HTTP_NOT_FOUND);
            }

            return new JsonResponse($statement, Response::HTTP_OK);
        }

        $registration = trim((string) $this->httpRequest->query->get('registration', ''));
        $statements = array_values($_SESSION['xapi_statement_store'] ?? []);

        if ('' !== $registration) {
            $statements = array_values(
                array_filter(
                    $statements,
                    fn ($statement): bool => \is_array($statement) && $this->matchesRegistration($statement, $registration)
                )
            );
        }

        return new JsonResponse(
            [
                'statements' => $statements,
                'more' => '',
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
        $statement = $this->decodeSingleStatement($this->httpRequest->getContent());

        $statementId = trim((string) $this->httpRequest->query->get('statementId', ''));

        if ('' === $statementId) {
            $statementId = $this->extractStatementId($statement);
        }

        if ('' === $statementId) {
            $statementId = Uuid::v4()->toRfc4122();
        }

        $statement['id'] = $statementId;

        $this->storeStatement($statementId, $statement);

        return new Response('', Response::HTTP_NO_CONTENT);
    }

    public function post(): Response
    {
        $statements = $this->decodeStatementCollection($this->httpRequest->getContent());

        $storedIds = [];

        foreach ($statements as $statement) {
            $statementId = $this->extractStatementId($statement);

            if ('' === $statementId) {
                $statementId = Uuid::v4()->toRfc4122();
            }

            $statement['id'] = $statementId;

            $this->storeStatement($statementId, $statement);
            $storedIds[] = $statementId;
        }

        return new JsonResponse($storedIds, Response::HTTP_OK);
    }

    private function storeStatement(string $statementId, array $statement): void
    {
        $this->ensureSessionStarted();

        if (!isset($_SESSION['xapi_statement_store']) || !\is_array($_SESSION['xapi_statement_store'])) {
            $_SESSION['xapi_statement_store'] = [];
        }

        $_SESSION['xapi_statement_store'][$statementId] = $statement;
    }

    private function decodeSingleStatement(string $content): array
    {
        $content = trim($content);

        if ('' === $content) {
            throw new \RuntimeException('Statement payload is empty.');
        }

        $decoded = json_decode($content, true);

        if (JSON_ERROR_NONE !== json_last_error() || !\is_array($decoded)) {
            throw new \RuntimeException('Invalid statement JSON payload.');
        }

        if ($this->isSequentialArray($decoded)) {
            throw new \RuntimeException('PUT /statements expects a single statement object.');
        }

        return $decoded;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function decodeStatementCollection(string $content): array
    {
        $content = trim($content);

        if ('' === $content) {
            throw new \RuntimeException('Statement payload is empty.');
        }

        $decoded = json_decode($content, true);

        if (JSON_ERROR_NONE !== json_last_error() || !\is_array($decoded)) {
            throw new \RuntimeException('Invalid statement JSON payload.');
        }

        if ($this->isSequentialArray($decoded)) {
            $statements = [];

            foreach ($decoded as $statement) {
                if (!\is_array($statement)) {
                    throw new \RuntimeException('Invalid statement entry in collection.');
                }

                $statements[] = $statement;
            }

            return $statements;
        }

        return [$decoded];
    }

    private function extractStatementId(array $statement): string
    {
        $statementId = $statement['id'] ?? '';

        return \is_string($statementId) ? trim($statementId) : '';
    }

    private function ensureSessionStarted(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }
    }

    private function isSequentialArray(array $array): bool
    {
        return array_keys($array) === range(0, \count($array) - 1);
    }

    private function matchesRegistration(array $statement, string $registration): bool
    {
        $topLevelRegistration = $statement['registration'] ?? null;
        if (\is_string($topLevelRegistration) && '' !== trim($topLevelRegistration)) {
            return trim($topLevelRegistration) === $registration;
        }

        $contextRegistration = $statement['context']['registration'] ?? null;
        if (\is_string($contextRegistration) && '' !== trim($contextRegistration)) {
            return trim($contextRegistration) === $registration;
        }

        return false;
    }
}
