<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Exercise;

use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\ExtraFieldValues;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/** Uses administrator-configured question fields. Never creates field definitions. */
final readonly class ExerciseQuestionBankClassification
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    /**
     * @return array<int, ExtraField>
     */
    public function getFields(): array
    {
        $fields = $this->entityManager->getRepository(ExtraField::class)->findBy(
            ['itemType' => ExtraField::QUESTION_FIELD_TYPE, 'visibleToSelf' => true],
            ['fieldOrder' => 'ASC', 'displayText' => 'ASC', 'id' => 'ASC']
        );
        $result = [];
        foreach ($fields as $field) {
            if (null !== $field->getId() && ($field->isFilter() || $field->isChangeable()) && \in_array($field->getValueType(), [
                ExtraField::FIELD_TYPE_TEXT,
                ExtraField::FIELD_TYPE_TEXTAREA,
                ExtraField::FIELD_TYPE_SELECT,
                ExtraField::FIELD_TYPE_RADIO,
            ], true)) {
                $result[$field->getId()] = $field;
            }
        }

        return $result;
    }

    /**
     * @param array<int, ExtraField> $fields
     */
    public function getDefinitions(array $fields): array
    {
        $definitions = [];
        foreach ($fields as $id => $field) {
            $options = [];
            foreach ($field->getOptions() as $option) {
                $value = (string) $option->getValue();
                if ('' === $value) {
                    continue;
                }
                $options[] = ['value' => $value, 'label' => (string) ($option->getDisplayText() ?: $value)];
            }
            $definitions[] = [
                'id' => $id,
                'label' => (string) ($field->getDisplayText() ?: $field->getVariable()),
                'type' => $field->getValueType(),
                'filter' => $field->isFilter(),
                'changeable' => true === $field->isChangeable(),
                'options' => $options,
            ];
        }

        return $definitions;
    }

    /**
     * @param array<int, ExtraField> $fields
     */
    public function getFilters(Request $request, array $fields): array
    {
        $filters = [];
        foreach ($request->query->all() as $key => $value) {
            if (!str_starts_with((string) $key, 'questionField_')) {
                continue;
            }
            $id = substr((string) $key, \strlen('questionField_'));
            if (!ctype_digit($id) || !isset($fields[(int) $id]) || !$fields[(int) $id]->isFilter() || !\is_string($value) || mb_strlen($value) > 4000) {
                throw new BadRequestHttpException('Invalid question classification filter.');
            }
            if ('' !== $value) {
                $filters[(int) $id] = $value;
            }
        }

        return $filters;
    }

    public function applyFilters(QueryBuilder $queryBuilder, array $filters): void
    {
        $index = 0;
        foreach ($filters as $id => $value) {
            ++$index;
            $alias = 'bankFieldValue'.$index;
            $field = 'bankFieldId'.$index;
            $parameter = 'bankFieldFilter'.$index;
            $subquery = $this->entityManager->createQueryBuilder()
                ->select('1')
                ->from(ExtraFieldValues::class, $alias)
                ->where($alias.'.itemId = question.iid')
                ->andWhere('IDENTITY('.$alias.'.field) = :'.$field)
                ->andWhere($alias.'.fieldValue = :'.$parameter)
            ;
            $queryBuilder->andWhere($queryBuilder->expr()->exists($subquery->getDQL()))
                ->setParameter($field, (int) $id)
                ->setParameter($parameter, $value)
            ;
        }
    }

    /**
     * @param array<int, ExtraField> $fields
     */
    public function getValues(array $ids, array $fields): array
    {
        if ([] === $ids || [] === $fields) {
            return [];
        }
        $rows = $this->entityManager->createQueryBuilder()
            ->select('value.itemId AS questionId', 'IDENTITY(value.field) AS fieldId', 'value.fieldValue AS fieldValue')
            ->from(ExtraFieldValues::class, 'value')
            ->where('value.itemId IN (:ids) AND IDENTITY(value.field) IN (:fields)')
            ->setParameter('ids', $ids)
            ->setParameter('fields', array_keys($fields))
            ->orderBy('value.id', 'ASC')
            ->getQuery()->getArrayResult()
        ;
        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row['questionId']][(int) $row['fieldId']] = (string) $row['fieldValue'];
        }

        return $result;
    }

    /**
     * Only submitted, visible and changeable question fields may be changed.
     */
    public function save(int $questionId, array $values): void
    {
        $fields = $this->getFields();
        if ([] === $values) {
            throw new BadRequestHttpException('Select at least one classification field.');
        }
        // Validate the whole payload before touching any stored values.
        foreach ($values as $id => $value) {
            $field = $fields[$id] ?? null;
            if (!$field instanceof ExtraField || !$field->isChangeable() || !\is_string($value) || mb_strlen($value) > 4000) {
                throw new BadRequestHttpException('Invalid question classification value.');
            }
            if ('' !== $value && \in_array($field->getValueType(), [ExtraField::FIELD_TYPE_SELECT, ExtraField::FIELD_TYPE_RADIO], true)) {
                $options = [];
                foreach ($field->getOptions() as $option) {
                    $options[] = (string) $option->getValue();
                }
                if (!\in_array($value, $options, true)) {
                    throw new BadRequestHttpException('Invalid question classification value.');
                }
            }
        }
        foreach ($values as $id => $value) {
            $field = $fields[$id];
            $rows = $this->entityManager->getRepository(ExtraFieldValues::class)->findBy(['itemId' => $questionId, 'field' => $field], ['id' => 'ASC']);
            if ([] === $rows) {
                if ('' === $value) {
                    continue;
                }
                $row = (new ExtraFieldValues())->setItemId($questionId)->setField($field)->setFieldValue($value);
                $this->entityManager->persist($row);

                continue;
            }
            // Keep existing rows; update duplicates consistently rather than deleting historical metadata.
            foreach ($rows as $row) {
                $row->setFieldValue($value);
            }
        }
    }
}
