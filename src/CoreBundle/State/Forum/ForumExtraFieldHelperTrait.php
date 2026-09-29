<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Forum;

use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\ExtraFieldValues;
use Chamilo\CoreBundle\Repository\ExtraFieldRepository;
use Chamilo\CoreBundle\Repository\ExtraFieldValuesRepository;
use Doctrine\ORM\EntityManagerInterface;

trait ForumExtraFieldHelperTrait
{
    private function getForumExtraFieldValue(
        ExtraFieldValuesRepository $extraFieldValuesRepository,
        string $itemType,
        int $itemId,
        string $variable,
    ): mixed {
        $itemTypeId = $this->getForumExtraFieldItemTypeId($itemType);
        if (null === $itemTypeId || $itemId <= 0) {
            return null;
        }

        $extraFieldValue = $extraFieldValuesRepository->getValueByVariableAndItem($variable, $itemId, $itemTypeId);

        return $extraFieldValue instanceof ExtraFieldValues ? $extraFieldValue->getFieldValue() : null;
    }

    private function saveForumExtraFieldValue(
        EntityManagerInterface $entityManager,
        ExtraFieldRepository $extraFieldRepository,
        ExtraFieldValuesRepository $extraFieldValuesRepository,
        string $itemType,
        int $itemId,
        string $variable,
        mixed $value,
    ): void {
        $itemTypeId = $this->getForumExtraFieldItemTypeId($itemType);
        if (null === $itemTypeId || $itemId <= 0) {
            return;
        }

        $extraField = $extraFieldRepository->findByVariable($itemTypeId, $variable);
        if (!$extraField instanceof ExtraField) {
            return;
        }

        $extraFieldValue = $extraFieldValuesRepository->getValueByVariableAndItem($variable, $itemId, $itemTypeId);
        $normalizedValue = null === $value ? '' : trim((string) $value);

        if ('' === $normalizedValue) {
            if ($extraFieldValue instanceof ExtraFieldValues) {
                $entityManager->remove($extraFieldValue);
                $entityManager->flush();
            }

            return;
        }

        if (!$extraFieldValue instanceof ExtraFieldValues) {
            $extraFieldValue = (new ExtraFieldValues())
                ->setField($extraField)
                ->setItemId($itemId)
            ;
        }

        $extraFieldValue->setFieldValue($normalizedValue);
        $entityManager->persist($extraFieldValue);
        $entityManager->flush();
    }

    private function getForumExtraFieldItemTypeId(string $itemType): ?int
    {
        return match ($itemType) {
            'course' => ExtraField::COURSE_FIELD_TYPE,
            'forum_category' => ExtraField::FORUM_CATEGORY_TYPE,
            'forum_post' => ExtraField::FORUM_POST_TYPE,
            default => null,
        };
    }
}
