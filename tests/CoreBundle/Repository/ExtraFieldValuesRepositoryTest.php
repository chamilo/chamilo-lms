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

    public function testCreate(): void
    {
        $em = $this->getEntityManager();

        $field = (new ExtraField())
            ->setDisplayText('test')
            ->setVariable('test')
            ->setExtraFieldType(ExtraField::USER_FIELD_TYPE)
            ->setFieldType(\ExtraField::FIELD_TYPE_TEXT)
        ;
        $em->persist($field);
        $em->flush();

        $user = $this->createUser('test');

        $extraFieldValue = (new ExtraFieldValues())
            ->setField($field)
            ->setItemId($user->getId())
            ->setValue('test')
        ;
        $this->assertHasNoEntityViolations($extraFieldValue);
        $em->persist($extraFieldValue);
        $em->flush();
    }

    public function testGetVisibleValues(): void
    {
        $repo = self::getContainer()->get(ExtraFieldValuesRepository::class);
        $values = $repo->getVisibleValues(0, 0);

        $this->assertCount(0, $values);
    }
}
