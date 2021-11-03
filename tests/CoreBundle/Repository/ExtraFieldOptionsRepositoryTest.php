<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\ExtraFieldOptions;
use Chamilo\CoreBundle\Repository\ExtraFieldOptionsRepository;
use Chamilo\CoreBundle\Repository\ExtraFieldRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use Gedmo\Translatable\Entity\Translation;

class ExtraFieldOptionsRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testCreate(): void
    {
        $em = $this->getEntityManager();
        $extraFieldRepo = static::getContainer()->get(ExtraFieldRepository::class);
        $extraFieldOptionsRepo = static::getContainer()->get(ExtraFieldOptionsRepository::class);

        $defaultCount = $extraFieldRepo->count([]);
        $defaultCountOptions = $extraFieldOptionsRepo->count([]);

        $extraField = (new ExtraField())
            ->setDisplayText('test')
            ->setVariable('test')
            ->setDescription('desc')
            ->setHelperText('help')
            ->setExtraFieldType(ExtraField::USER_FIELD_TYPE)
            ->setFieldType(\ExtraField::FIELD_TYPE_TEXT)
        ;
        $em->persist($extraField);

        $extraFieldOptions = (new ExtraFieldOptions())
            ->setDisplayText('test in ENGLISH')
            ->setValue('value')
            ->setField($extraField)
            ->setOptionOrder(0)
            ->setPriority('urgent')
            ->setPriorityMessage('is urgent!')
        ;
        $this->assertHasNoEntityViolations($extraFieldOptions);
        $em->persist($extraFieldOptions);
        $em->flush();

        $this->assertSame('test in ENGLISH', $extraFieldOptions->getDisplayText());
        $this->assertSame('value', $extraFieldOptions->getValue());
        $this->assertSame(0, $extraFieldOptions->getOptionOrder());
        $this->assertSame('urgent', $extraFieldOptions->getPriority());
        $this->assertSame('is urgent!', $extraFieldOptions->getPriorityMessage());

        $this->assertSame($defaultCount + 1, $extraFieldRepo->count([]));
        $this->assertSame($defaultCountOptions + 1, $extraFieldOptionsRepo->count([]));
    }

    public function testCreateWithTranslation(): void
    {
        $this->testCreate();
        $em = $this->getEntityManager();

        $extraFieldOptionsRepo = static::getContainer()->get(ExtraFieldOptionsRepository::class);

        /** @var ExtraFieldOptions $extraFieldOption */
        $extraFieldOption = $extraFieldOptionsRepo->findOneBy(['value' => 'value']);
        $this->assertNotNull($extraFieldOption);
        $this->assertInstanceOf(ExtraFieldOptions::class, $extraFieldOption);

        $extraFieldOption
            ->setTranslatableLocale('fr_FR')
            ->setDisplayText('test in FRENCH')
        ;
        $em->persist($extraFieldOption);
        $em->flush();

        /** @var ExtraFieldOptions $extraFieldOption */
        $extraFieldOption = $extraFieldOptionsRepo->find($extraFieldOption->getId());

        $extraFieldOption
            ->setTranslatableLocale('pl')
            ->setDisplayText('test in POLISH')
        ;
        $em->persist($extraFieldOption);
        $em->flush();
        $em->clear();

        /** @var ExtraFieldOptions $extraFieldOption */
        $extraFieldOption = $extraFieldOptionsRepo->find($extraFieldOption->getId());
        $repository = $em->getRepository(Translation::class);

        $translations = $repository->findTranslations($extraFieldOption);

        $this->assertCount(2, $translations);
        $expected = [
            'fr_FR' => [
                'displayText' => 'test in FRENCH',
            ],
            'pl' => [
                'displayText' => 'test in POLISH',
            ],
        ];
        $this->assertSame($expected, $translations);

        /** @var ExtraFieldOptions $extraFieldOption */
        $extraFieldOption = $extraFieldOptionsRepo->find($extraFieldOption->getId());
        $this->assertSame('test in ENGLISH', $extraFieldOption->getDisplayText());
    }
}
