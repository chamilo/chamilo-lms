<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\ExtraFieldOptions;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ExtraFieldRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ExtraField::class);
    }

    /**
     * @return ExtraField[]
     */
    public function getExtraFields(int $type): array
    {
        $qb = $this->createQueryBuilder('f');
        $qb
            ->addSelect(
                'CASE WHEN f.fieldOrder IS NULL THEN -1 ELSE f.fieldOrder END AS HIDDEN list_order_is_null'
            )
            ->where(
                $qb->expr()->eq('f.visibleToSelf', true),
            )
            ->andWhere(
                $qb->expr()->eq('f.itemType', $type)
            )
            ->orderBy('list_order_is_null', 'ASC')
        ;

        return $qb->getQuery()->getResult();
    }

    public function getHandlerFieldInfoByFieldVariable(string $variable, int $itemType): bool|array
    {
        $extraField = $this->findOneBy([
            'variable' => $variable,
            'itemType' => $itemType,
        ]);

        if (!$extraField) {
            return false;
        }

        $fieldInfo = [
            'id' => $extraField->getId(),
            'variable' => $extraField->getVariable(),
            'display_text' => $extraField->getDisplayText(),
            'type' => $extraField->getValueType(),
            'options' => [],
        ];

        $options = $this->_em->getRepository(ExtraFieldOptions::class)->findBy([
            'field' => $extraField,
        ]);

        foreach ($options as $option) {
            $fieldInfo['options'][$option->getId()] = [
                'id' => $option->getId(),
                'value' => $option->getValue(),
                'display_text' => $option->getDisplayText(),
                'option_order' => $option->getOptionOrder(),
            ];
        }

        return $fieldInfo;
    }

    public function findByVariable(int $itemType, string $variable): ?ExtraField
    {
        return $this->findOneBy(['variable' => $variable, 'itemType' => $itemType]);
    }
}
