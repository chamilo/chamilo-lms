<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\ExtraFieldValues;
use Chamilo\CoreBundle\Repository\ExtraFieldValuesRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

class ExtraFieldValuesRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testGetVisibleValues(): void
    {
        $repo = self::getContainer()->get(ExtraFieldValuesRepository::class);
        $values = $repo->getVisibleValues(0, 0);

        $this->assertCount(0, $values);
    }

    public function testGetByItemIdsAndFieldIds(): void
    {
        $em = $this->getEntityManager();

        /** @var ExtraFieldValuesRepository $repo */
        $repo = self::getContainer()->get(ExtraFieldValuesRepository::class);

        $fieldA = (new ExtraField())
            ->setDisplayText('batch a')
            ->setVariable('batch_a')
            ->setItemType(ExtraField::USER_FIELD_TYPE)
            ->setValueType(\ExtraField::FIELD_TYPE_TEXT)
        ;
        $fieldB = (new ExtraField())
            ->setDisplayText('batch b')
            ->setVariable('batch_b')
            ->setItemType(ExtraField::USER_FIELD_TYPE)
            ->setValueType(\ExtraField::FIELD_TYPE_TEXT)
        ;
        $em->persist($fieldA);
        $em->persist($fieldB);
        $em->flush();

        $userOne = $this->createUser('batch_user_one');
        $userTwo = $this->createUser('batch_user_two');

        $valueOneA = (new ExtraFieldValues())->setField($fieldA)->setItemId($userOne->getId())->setFieldValue('one-a');
        $valueTwoB = (new ExtraFieldValues())->setField($fieldB)->setItemId($userTwo->getId())->setFieldValue('two-b');
        $em->persist($valueOneA);
        $em->persist($valueTwoB);
        $em->flush();

        $values = $repo->getByItemIdsAndFieldIds(
            [(int) $userOne->getId(), (int) $userTwo->getId()],
            [(int) $fieldA->getId(), (int) $fieldB->getId()],
            ExtraField::USER_FIELD_TYPE,
        );

        $this->assertCount(2, $values);

        $byItemAndField = [];
        foreach ($values as $value) {
            $byItemAndField[$value->getItemId()][$value->getField()->getId()] = $value->getFieldValue();
        }
        $this->assertSame('one-a', $byItemAndField[$userOne->getId()][$fieldA->getId()]);
        $this->assertSame('two-b', $byItemAndField[$userTwo->getId()][$fieldB->getId()]);

        // Empty inputs must not run a query with an empty IN() clause.
        $this->assertSame([], $repo->getByItemIdsAndFieldIds([], [(int) $fieldA->getId()], ExtraField::USER_FIELD_TYPE));
        $this->assertSame([], $repo->getByItemIdsAndFieldIds([(int) $userOne->getId()], [], ExtraField::USER_FIELD_TYPE));
    }

    public function testUpdateItemData(): void
    {
        /** @var ExtraFieldValuesRepository $repo */
        $repo = self::getContainer()->get(ExtraFieldValuesRepository::class);

        $em = $this->getEntityManager();

        // User extra field.
        $field = (new ExtraField())
            ->setDisplayText('test')
            ->setVariable('test')
            ->setVisibleToSelf(true)
            ->setItemType(ExtraField::USER_FIELD_TYPE)
            ->setValueType(\ExtraField::FIELD_TYPE_TEXT)
        ;
        $em->persist($field);
        $em->flush();

        $user = $this->createUser('test');

        /** @var ExtraFieldValues $extraFieldValue */
        $extraFieldValue = $repo->updateItemData($field, $user, 'test');

        $items = $repo->getExtraFieldValuesFromItem($user, ExtraField::USER_FIELD_TYPE);

        $this->assertNotNull($items);
        $this->assertNotNull($extraFieldValue);
        $this->assertCount(1, $items);

        // Course extra field.

        $field = (new ExtraField())
            ->setDisplayText('test2')
            ->setVariable('test2')
            ->setVisibleToSelf(true)
            ->setItemType(ExtraField::COURSE_FIELD_TYPE)
            ->setValueType(\ExtraField::FIELD_TYPE_TEXT)
        ;
        $em->persist($field);
        $em->flush();

        $course = $this->createCourse('new');
        $this->assertSame($course->getResourceIdentifier(), $course->getId());
        $extraFieldValue = $repo->updateItemData($field, $course, 'julio');

        $this->assertSame('julio', $extraFieldValue->getFieldValue());

        $extraFieldValue = $repo->updateItemData($field, $course, 'casa');

        $this->assertSame('casa', $extraFieldValue->getFieldValue());

        $items = $repo->getExtraFieldValuesFromItem($course, ExtraField::COURSE_FIELD_TYPE);
        $this->assertNotNull($extraFieldValue);
        $this->assertCount(1, $items);
    }
}
