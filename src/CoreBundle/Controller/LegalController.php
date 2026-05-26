<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\Legal;
use Chamilo\CoreBundle\Repository\LegalRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

use const JSON_UNESCAPED_UNICODE;

#[IsGranted('ROLE_USER')]
#[Route('/legal')]
class LegalController
{
    #[Route('/save', name: 'chamilo_core_legal_save', methods: ['POST'])]
    public function saveLegal(
        Request $request,
        EntityManagerInterface $entityManager,
        LegalRepository $legalRepository
    ): Response {
        $data = json_decode($request->getContent(), true);

        $languageId = (int) ($data['lang'] ?? 0);
        if ($languageId <= 0) {
            return new JsonResponse(['message' => 'Invalid language id'], Response::HTTP_BAD_REQUEST);
        }

        $changes = (string) ($data['changes'] ?? '');
        $sections = $data['sections'] ?? null;

        if (!\is_array($sections)) {
            return new JsonResponse(['message' => 'Missing sections payload'], Response::HTTP_BAD_REQUEST);
        }

        // Normalize & hash incoming payload (idempotency).
        $normalize = static function ($v): string {
            $s = \is_string($v) ? $v : '';
            $s = str_replace(["\r\n", "\r"], "\n", $s);

            return trim($s);
        };

        $incoming = [];
        for ($type = 0; $type <= 15; $type++) {
            $key = (string) $type;
            $incoming[$type] = $normalize($sections[$key] ?? ($sections[$type] ?? ''));
        }
        $incomingHash = hash('sha256', json_encode($incoming, JSON_UNESCAPED_UNICODE));

        $conn = $entityManager->getConnection();
        $conn->beginTransaction();

        try {
            // Generic concurrency control: lock the language row.
            // This prevents two concurrent saves for the same language.
            $conn->executeStatement(
                'SELECT id FROM language WHERE id = :id FOR UPDATE',
                ['id' => $languageId]
            );

            // Get latest version (inside the lock).
            $lastVersion = (int) $legalRepository->findLatestVersionByLanguage($languageId);

            if ($lastVersion > 0) {
                // Load last saved sections to compare.
                $rows = $conn->fetchAllAssociative(
                    'SELECT type, content
                 FROM legal
                 WHERE language_id = :lang AND version = :ver
                 ORDER BY type ASC',
                    ['lang' => $languageId, 'ver' => $lastVersion]
                );

                $existing = array_fill(0, 16, '');
                foreach ($rows as $r) {
                    $t = (int) ($r['type'] ?? -1);
                    if ($t >= 0 && $t <= 15) {
                        $existing[$t] = $normalize($r['content'] ?? '');
                    }
                }
                $existingHash = hash('sha256', json_encode($existing, JSON_UNESCAPED_UNICODE));

                // No changes => do NOT create a new version.
                if ($existingHash === $incomingHash) {
                    $conn->commit();

                    return new JsonResponse([
                        'message' => 'No changes detected',
                        'version' => $lastVersion,
                    ], Response::HTTP_OK);
                }
            }

            // Create new version only when changed.
            $newVersion = $lastVersion + 1;
            $timestamp = time();

            for ($type = 0; $type <= 15; $type++) {
                $content = $incoming[$type];

                $legal = new Legal();
                $legal->setLanguageId($languageId);
                $legal->setVersion($newVersion);
                $legal->setType($type);
                $legal->setDate($timestamp);
                $legal->setChanges($changes);
                $legal->setContent('' === $content ? null : $content);

                $entityManager->persist($legal);
            }

            $entityManager->flush();
            $conn->commit();

            return new JsonResponse([
                'message' => 'Terms saved successfully',
                'version' => $newVersion,
            ], Response::HTTP_OK);
        } catch (Throwable $e) {
            $conn->rollBack();

            throw $e;
        }
    }

    #[Route('/extra-fields', name: 'chamilo_core_get_extra_fields')]
    public function getExtraFields(Request $request): JsonResponse
    {
        return new JsonResponse([
            ['type' => 0, 'title' => 'Terms and Conditions', 'subtitle' => ''],
            ['type' => 1, 'title' => 'Personal data collection', 'subtitle' => 'Why do we collect this data?'],
            ['type' => 15, 'title' => 'Personal data profiling', 'subtitle' => 'For what purpose do we process personal data?'],
        ]);
    }
}
