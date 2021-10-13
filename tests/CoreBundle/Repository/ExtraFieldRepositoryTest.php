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
        $repo = self::getContainer()->get(ExtraFieldRepository::class);

        $defaultCount = $repo->count([]);

        $item = (new ExtraField())
            ->setDisplayText('test')
            ->setVariable('test')
            ->setExtraFieldType(ExtraField::USER_FIELD_TYPE)
            ->setFieldType(\ExtraField::FIELD_TYPE_TEXT)
        ;
        $this->assertHasNoEntityViolations($item);
        $em->persist($item);
        $em->flush();

        $this->assertSame($defaultCount + 1, $repo->count([]));
    }

    public function testGetExtraFields(): void
    {
        $repo = self::getContainer()->get(ExtraFieldRepository::class);
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
            ->setExtraFieldType(ExtraField::USER_FIELD_TYPE)
            ->setFieldType(\ExtraField::FIELD_TYPE_TEXT)
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
