<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\Lrs;

use Chamilo\CoreBundle\Entity\XApiActivityState;
use Database;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ActivitiesStateController.
 */
class ActivitiesStateController extends BaseController
{
    public function get(): Response
    {
        $activityId = trim((string) $this->httpRequest->query->get('activityId'));
        $stateId = trim((string) $this->httpRequest->query->get('stateId'));
        $requestedAgentRaw = (string) $this->httpRequest->query->get('agent', '');

        if ('' === $activityId || '' === $stateId || '' === trim($requestedAgentRaw)) {
            return new JsonResponse(
                ['error' => 'Missing required parameters.'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $requestedAgent = $this->decodeJsonObject($requestedAgentRaw);
        if (null === $requestedAgent) {
            return new JsonResponse(
                ['error' => 'Invalid agent JSON.'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $rows = Database::select(
            '*',
            Database::get_main_table('xapi_activity_state'),
            [
                'where' => [
                    'state_id = ? AND activity_id = ?' => [
                        Database::escape_string($stateId),
                        Database::escape_string($activityId),
                    ],
                ],
            ]
        );

        if (empty($rows)) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        foreach ($rows as $row) {
            $storedAgent = $this->normalizeDecodedValue($row['agent'] ?? null);

            if (!$this->agentsAreEqual($requestedAgent, $storedAgent)) {
                continue;
            }

            $documentData = $this->normalizeDecodedValue($row['document_data'] ?? null);

            if (null === $documentData) {
                $documentData = [];
            }

            return new JsonResponse($documentData, Response::HTTP_OK);
        }

        return new Response('', Response::HTTP_NOT_FOUND);
    }

    public function head(): Response
    {
        return $this->get()->setContent('');
    }

    public function post(): Response
    {
        return $this->put();
    }

    public function put(): Response
    {
        $activityId = trim((string) $this->httpRequest->query->get('activityId'));
        $stateId = trim((string) $this->httpRequest->query->get('stateId'));
        $agentRaw = (string) $this->httpRequest->query->get('agent', '');
        $documentDataRaw = (string) $this->httpRequest->getContent();

        if ('' === $activityId || '' === $stateId || '' === trim($agentRaw)) {
            return new JsonResponse(
                ['error' => 'Missing required parameters.'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $decodedAgent = $this->decodeJsonObject($agentRaw);
        if (null === $decodedAgent) {
            return new JsonResponse(
                ['error' => 'Invalid agent JSON.'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $decodedDocumentData = $this->decodeJsonObject($documentDataRaw);
        if (null === $decodedDocumentData) {
            return new JsonResponse(
                ['error' => 'Invalid document JSON.'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $normalizedAgentJson = $this->encodeCanonicalJson($decodedAgent);

        $stateRow = Database::select(
            'id, agent',
            Database::get_main_table('xapi_activity_state'),
            [
                'where' => [
                    'state_id = ? AND activity_id = ?' => [
                        Database::escape_string($stateId),
                        Database::escape_string($activityId),
                    ],
                ],
            ]
        );

        $em = Database::getManager();
        $stateEntity = null;

        foreach ($stateRow as $row) {
            $storedAgent = $this->normalizeDecodedValue($row['agent'] ?? null);

            if ($this->agentsAreEqual($decodedAgent, $storedAgent)) {
                $stateEntity = $em->find(XApiActivityState::class, (int) $row['id']);
                break;
            }
        }

        if (!$stateEntity instanceof XApiActivityState) {
            $stateEntity = new XApiActivityState();
            $stateEntity
                ->setActivityId($activityId)
                ->setStateId($stateId)
                ->setAgent(json_decode($normalizedAgentJson, true))
            ;
        }

        $stateEntity->setDocumentData($decodedDocumentData);

        $em->persist($stateEntity);
        $em->flush();

        return new Response('', Response::HTTP_NO_CONTENT);
    }

    private function decodeJsonObject(string $json): ?array
    {
        $json = trim($json);

        if ('' === $json) {
            return null;
        }

        $decoded = json_decode($json, true);

        if (JSON_ERROR_NONE !== json_last_error() || !\is_array($decoded)) {
            return null;
        }

        $this->sortArrayRecursively($decoded);

        return $decoded;
    }

    private function normalizeDecodedValue(mixed $value): ?array
    {
        if (\is_array($value)) {
            $this->sortArrayRecursively($value);

            return $value;
        }

        if (\is_string($value) && '' !== trim($value)) {
            return $this->decodeJsonObject($value);
        }

        return null;
    }

    private function agentsAreEqual(?array $left, ?array $right): bool
    {
        if (null === $left || null === $right) {
            return false;
        }

        return $this->encodeCanonicalJson($left) === $this->encodeCanonicalJson($right);
    }

    private function encodeCanonicalJson(array $data): string
    {
        $copy = $data;
        $this->sortArrayRecursively($copy);

        $json = json_encode($copy, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return false === $json ? '' : $json;
    }

    private function sortArrayRecursively(array &$data): void
    {
        foreach ($data as &$value) {
            if (\is_array($value)) {
                $this->sortArrayRecursively($value);
            }
        }
        unset($value);

        if ($this->isAssoc($data)) {
            ksort($data);
        }
    }

    private function isAssoc(array $array): bool
    {
        return array_keys($array) !== range(0, \count($array) - 1);
    }
}
