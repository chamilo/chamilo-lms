<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\ExtraFieldSavedSearch;
use Chamilo\CoreBundle\Repository\ExtraFieldRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

class ExtraFieldRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testCreate(): void
    {
        $em = $this->getEntityManager();
        $repo = static::getContainer()->get(ExtraFieldRepository::class);

        $defaultCount = $repo->count([]);

        $extraField = (new ExtraField())
            ->setDisplayText('test')
            ->setVariable('test')
            ->setDescription('desc')
            ->setHelperText('help')
            ->setItemType(ExtraField::USER_FIELD_TYPE)
            ->setValueType(\ExtraField::FIELD_TYPE_TEXT)
            ->setDefaultValue('')
            ->setFieldOrder(1)
        ;
        $this->assertHasNoEntityViolations($extraField);
        $em->persist($extraField);
        $em->flush();

        $this->assertSame($defaultCount + 1, $repo->count([]));
        $this->assertSame(ExtraField::USER_FIELD_TYPE, $extraField->getItemType());
        $this->assertSame('', $extraField->getDefaultValue());
        $this->assertSame('desc', $extraField->getDescription());
        $this->assertSame('text', $extraField->getTypeToString());
        $this->assertSame('help', $extraField->getHelperText());
        $this->assertSame(1, $extraField->getFieldOrder());

        $this->assertNotNull($extraField->getId());
        $this->assertFalse($extraField->isChangeable());
        $this->assertFalse($extraField->isFilter());
        $this->assertFalse($extraField->isVisibleToSelf());
        $this->assertFalse($extraField->isVisibleToOthers());
        $this->assertFalse($extraField->hasTag('tag'));
        $this->assertNotNull($extraField->getId());

        $this->assertSame($defaultCount + 1, $repo->count([]));
    }

    public function testGetExtraFields(): void
    {
        $repo = static::getContainer()->get(ExtraFieldRepository::class);
        $this->assertNotNull($repo->getExtraFields(ExtraField::USER_FIELD_TYPE));
    }

    public function testCreateExtraFieldSavedSearch(): void
    {
        $em = $this->getEntityManager();
        $extraFieldSavedSearchRepo = $em->getRepository(ExtraFieldSavedSearch::class);

        $student = $this->createUser('student');

        $item = (new ExtraField())
            ->setDisplayText('test')
            ->setVariable('test')
            ->setItemType(ExtraField::USER_FIELD_TYPE)
            ->setValueType(\ExtraField::FIELD_TYPE_TEXT)
        ;
        $em->persist($item);

        $value = '2020-11-24';
        $extraFieldSavedSearch = (new ExtraFieldSavedSearch())
            ->setField($item)
            ->setUser($student)
            ->setValue([$value])
        ;
        $this->assertHasNoEntityViolations($extraFieldSavedSearch);
        $em->persist($extraFieldSavedSearch);
        $em->flush();

        $this->assertSame(1, $extraFieldSavedSearchRepo->count([]));
        $this->assertIsArray($extraFieldSavedSearch->getValue());
        $this->assertSame($value, $extraFieldSavedSearch->getValue()[0]);
        $this->assertNotNull($extraFieldSavedSearch->getUser());
        $this->assertNotNull($extraFieldSavedSearch->getField());
        $this->assertNotNull($extraFieldSavedSearch->getCreatedAt());
        $this->assertNotNull($extraFieldSavedSearch->getUpdatedAt());
    }
}
