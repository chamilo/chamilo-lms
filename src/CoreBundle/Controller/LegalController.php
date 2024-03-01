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
use Symfony\Component\Routing\Annotation\Route;

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

        $lang = $data['lang'] ?? null;
        $content = $data['content'] ?? null;
        $type = isset($data['type']) ? (int) $data['type'] : null;
        $changes = $data['changes'] ?? '';
        $extraFields = $data['extraFields'] ?? [];

        $lastLegal = $legalRepository->findLastConditionByLanguage($lang);
        $extraFieldValue = new ExtraFieldValue('terms_and_condition');

        $newVersionRequired = !$lastLegal || $lastLegal->getContent() !== $content || $this->hasExtraFieldsChanged($extraFieldValue, $lastLegal->getId(), $extraFields);
        $typeUpdateRequired = $lastLegal && $lastLegal->getType() !== $type;

        $legalToUpdate = $lastLegal;
        if ($newVersionRequired) {
            $legal = new Legal();
            $legal->setLanguageId($lang);
            $legal->setContent($content);
            $legal->setType($type);
            $legal->setChanges($changes);
            $legal->setDate(time());
            $version = $lastLegal ? $lastLegal->getVersion() + 1 : 1;
            $legal->setVersion($version);

            $entityManager->persist($legal);
            $legalToUpdate = $legal;
        } elseif ($typeUpdateRequired) {
            $lastLegal->setType($type);
            $lastLegal->setChanges($changes);
        }

        $entityManager->flush();

        if ($newVersionRequired || $typeUpdateRequired) {
            $this->updateExtraFields($extraFieldValue, $legalToUpdate->getId(), $extraFields);
        }

        return new Response('Term and condition saved or updated successfully', Response::HTTP_OK);
    }

    #[Route('/extra-fields', name: 'chamilo_core_get_extra_fields')]
    public function getExtraFields(Request $request): JsonResponse
    {
        $extraField = new ExtraField('terms_and_condition');
        $types = LegalManager::getTreatmentTypeList();

        foreach ($types as $variable => $name) {
            $label = 'PersonalData'.ucfirst($name).'Title';
            $params = [
                'variable' => $variable,
                'display_text' => $label,
                'value_type' => ExtraField::FIELD_TYPE_TEXTAREA,
                'default_value' => '',
                'visible' => true,
                'changeable' => true,
                'filter' => true,
                'visible_to_self' => true,
                'visible_to_others' => true,
            ];
            $extraField->save($params);
        }

        $termId = $request->query->get('termId');
        $extraData = $extraField->get_handler_extra_data($termId ?? 0);
        $extraFieldsDefinition = $extraField->get_all();
        $fieldsData = $extraField->getExtraFieldsData(
            $extraData,
            true,
            $extraFieldsDefinition
        );

        $prefix = 'extra_';
        $extraFields = [];
        foreach ($fieldsData as $field) {
            $fieldType = $this->mapFieldType($field['type']);
            $extraField = [
                'id' => $prefix.$field['variable'],
                'type' => $fieldType,
                'props' => [
                    'title' => $field['title'],
                    'defaultValue' => $field['defaultValue'],
                ],
            ];

            switch ($fieldType) {
                case 'editor':
                    $extraField['props']['editorId'] = $prefix.$field['variable'];
                    $extraField['props']['modelValue'] = $field['value'] ?? '';
                    $extraField['props']['helpText'] = 'Specific help text for '.$field['title'];

                    break;

                case 'text':
                    $extraField['props']['label'] = $field['title'];
                    $extraField['props']['modelValue'] = $field['value'] ?? '';

                    break;

                case 'select':
                    $extraField['props']['label'] = $field['title'];
                    $extraField['props']['options'] = [];
                    $extraField['props']['modelValue'] = $field['value'] ?? '';

                    break;
            }

            $extraFields[] = $extraField;
        }

        return new JsonResponse($extraFields);
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
