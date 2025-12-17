<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\Legal;
use Chamilo\CoreBundle\Repository\LegalRepository;
use Doctrine\ORM\EntityManagerInterface;
use ExtraField;
use ExtraFieldValue;
use LegalManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

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

        if (!is_array($sections)) {
            return new JsonResponse(['message' => 'Missing sections payload'], Response::HTTP_BAD_REQUEST);
        }

        $lastVersion = $legalRepository->findLatestVersionByLanguage($languageId);
        $newVersion = $lastVersion + 1;
        $timestamp = time();

        for ($type = 0; $type <= 15; $type++) {
            $key = (string) $type;
            $content = $sections[$key] ?? ($sections[$type] ?? '');
            $content = is_string($content) ? $content : '';

            $legal = new Legal();
            $legal->setLanguageId($languageId);
            $legal->setVersion($newVersion);
            $legal->setType($type);
            $legal->setDate($timestamp);
            $legal->setChanges($changes);
            $legal->setContent($content === '' ? null : $content);

            $entityManager->persist($legal);
        }

        $entityManager->flush();

        return new JsonResponse([
            'message' => 'Terms saved successfully',
            'version' => $newVersion,
        ], Response::HTTP_OK);
    }

    #[Route('/extra-fields', name: 'chamilo_core_get_extra_fields')]
    public function getExtraFields(Request $request): JsonResponse
    {
        return new JsonResponse([
            ['type' => 0, 'title' => 'Terms and Conditions', 'subtitle' => ''],
            ['type' => 1, 'title' => 'Personal data collection', 'subtitle' => 'Why do we collect this data?'],
            // ...
            ['type' => 15, 'title' => 'Personal data profiling', 'subtitle' => 'For what purpose do we process personal data?'],
        ]);
    }

    /**
     * Checks if the extra field values have changed.
     *
     * This function compares the new values for extra fields against the old ones to determine
     * if there have been any changes. It is useful for triggering events or updates only when
     * actual changes to data occur.
     */
    private function hasExtraFieldsChanged(ExtraFieldValue $extraFieldValue, int $legalId, array $newValues): bool
    {
        $oldValues = $extraFieldValue->getAllValuesByItem($legalId);
        $oldValues = array_column($oldValues, 'value', 'variable');

        foreach ($newValues as $key => $newValue) {
            if (isset($oldValues[$key]) && $newValue != $oldValues[$key]) {
                return true;
            }
            if (!isset($oldValues[$key])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Updates the extra fields with new values for a specific item.
     */
    private function updateExtraFields(ExtraFieldValue $extraFieldValue, int $legalId, array $values): void
    {
        $values['item_id'] = $legalId;
        $extraFieldValue->saveFieldValues($values);
    }

    /**
     * Maps an integer representing a field type to its corresponding string value.
     */
    private function mapFieldType(int $type): string
    {
        switch ($type) {
            case ExtraField::FIELD_TYPE_TEXT:
                return 'text';

            case ExtraField::FIELD_TYPE_TEXTAREA:
                return 'editor';

            case ExtraField::FIELD_TYPE_SELECT_MULTIPLE:
            case ExtraField::FIELD_TYPE_DATE:
            case ExtraField::FIELD_TYPE_DATETIME:
            case ExtraField::FIELD_TYPE_DOUBLE_SELECT:
            case ExtraField::FIELD_TYPE_RADIO:
                // Manage as needed
                break;

            case ExtraField::FIELD_TYPE_SELECT:
                return 'select';
        }

        return 'text';
    }
}
