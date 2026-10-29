<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Controller\Admin;

use Chamilo\CoreBundle\Controller\Admin\UsergroupAddUsersController;
use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\ExtraFieldOptions;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

class UsergroupExtraFieldFilterTest extends TestCase
{
    public function testCheckboxChoicesAreExplicit(): void
    {
        $controller = (new ReflectionClass(UsergroupAddUsersController::class))->newInstanceWithoutConstructor();
        $field = (new ExtraField())->setValueType(ExtraField::FIELD_TYPE_CHECKBOX);

        $options = (new ReflectionMethod(UsergroupAddUsersController::class, 'getExtraFieldOptions'))
            ->invoke($controller, $field)
        ;

        self::assertSame([
            ['value' => '1', 'label' => 'Yes'],
            ['value' => '0', 'label' => 'No'],
        ], $options);
    }

    public function testFreeTextFieldsDoNotExposeChoiceOptions(): void
    {
        $controller = (new ReflectionClass(UsergroupAddUsersController::class))->newInstanceWithoutConstructor();
        $field = (new ExtraField())->setValueType(ExtraField::FIELD_TYPE_TEXT);

        $options = (new ReflectionMethod(UsergroupAddUsersController::class, 'getExtraFieldOptions'))
            ->invoke($controller, $field)
        ;

        self::assertSame([], $options);
    }

    public function testOnlyFilterEnabledUserFieldsAreReturned(): void
    {
        $field = (new ExtraField())
            ->setItemType(ExtraField::USER_FIELD_TYPE)
            ->setValueType(ExtraField::FIELD_TYPE_SELECT)
            ->setVariable('job_title')
            ->setDisplayText('Job title')
            ->setFilter(true)
        ;

        (new ReflectionProperty(ExtraField::class, 'id'))->setValue($field, 42);

        $option = (new ExtraFieldOptions())
            ->setValue('supervisor')
            ->setDisplayText('Supervisor')
        ;

        $fieldRepository = $this->createMock(EntityRepository::class);
        $fieldRepository->expects(self::once())->method('findBy')
            ->with(
                ['itemType' => ExtraField::USER_FIELD_TYPE, 'filter' => true],
                ['fieldOrder' => 'ASC', 'id' => 'ASC'],
            )
            ->willReturn([$field])
        ;

        $optionRepository = $this->createMock(EntityRepository::class);
        $optionRepository->expects(self::once())->method('findBy')
            ->with(['field' => $field], ['optionOrder' => 'ASC', 'id' => 'ASC'])
            ->willReturn([$option])
        ;

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturnCallback(
            static fn (string $class): EntityRepository => match ($class) {
                ExtraField::class => $fieldRepository,
                ExtraFieldOptions::class => $optionRepository,
            },
        );

        $controller = (new ReflectionClass(UsergroupAddUsersController::class))->newInstanceWithoutConstructor();
        (new ReflectionProperty(UsergroupAddUsersController::class, 'em'))->setValue($controller, $em);

        $fields = (new ReflectionMethod(UsergroupAddUsersController::class, 'getSearchableUserExtraFields'))
            ->invoke($controller)
        ;

        self::assertSame([[
            'id' => 42,
            'label' => 'Job title',
            'type' => ExtraField::FIELD_TYPE_SELECT,
            'options' => [['value' => 'supervisor', 'label' => 'Supervisor']],
        ]], $fields);
    }
}
