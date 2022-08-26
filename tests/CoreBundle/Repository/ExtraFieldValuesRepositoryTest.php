<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\Asset;
use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\ExtraFieldValues;
use Chamilo\CoreBundle\Repository\AssetRepository;
use Chamilo\CoreBundle\Repository\ExtraFieldValuesRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

class ExtraFieldValuesRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testCreate(): void
    {
        $em = $this->getEntityManager();
        /** @var AssetRepository $assetRepo */
        $assetRepo = self::getContainer()->get(AssetRepository::class);
        $extraFieldValueRepo = self::getContainer()->get(ExtraFieldValuesRepository::class);

        $field = (new ExtraField())
            ->setFieldOrder(1)
            ->setChangeable(true)
            ->setFilter(true)
            ->setHelperText('helper')
            ->setVisibleToOthers(true)
            ->setVisibleToSelf(true)
            ->setDisplayText('test')
            ->setVariable('test')
            ->setItemType(ExtraField::USER_FIELD_TYPE)
            ->setValueType(\ExtraField::FIELD_TYPE_TEXT)
        ;
        $em->persist($field);
        $em->flush();

        $user = $this->createUser('test');

        $file = $this->getUploadedFile();

        // Create asset.
        $asset = (new Asset())
            ->setTitle('file')
            ->setCategory(Asset::EXTRA_FIELD)
            ->setFile($file)
        ;
        $em->persist($asset);

        $extraFieldValue = (new ExtraFieldValues())
            ->setField($field)
            ->setItemId($user->getId())
            ->setValue('test')
            ->setComment('comment')
            ->setAsset($asset)
        ;
        $this->assertHasNoEntityViolations($extraFieldValue);
        $em->persist($extraFieldValue);
        $em->flush();

        $this->assertNotNull($extraFieldValue->getId());
        $this->assertSame('comment', $extraFieldValue->getComment());
        $this->assertSame('test', $extraFieldValue->getValue());
        $this->assertNotNull($extraFieldValue->getAsset());

        $this->assertSame(1, $assetRepo->count([]));
        $this->assertSame(1, $extraFieldValueRepo->count([]));
    }

    public function testGetVisibleValues(): void
    {
        $repo = self::getContainer()->get(ExtraFieldValuesRepository::class);
        $values = $repo->getVisibleValues(0, 0);

        $this->assertCount(0, $values);
    }

    public function testUpdateItemData(): void
    {
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

        $this->assertSame('julio', $extraFieldValue->getValue());

        $extraFieldValue = $repo->updateItemData($field, $course, 'casa');

        $this->assertSame('casa', $extraFieldValue->getValue());

        $items = $repo->getExtraFieldValuesFromItem($course, ExtraField::COURSE_FIELD_TYPE);
        $this->assertNotNull($extraFieldValue);
        $this->assertCount(1, $items);
    }
}
