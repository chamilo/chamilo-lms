<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Settings;

use Chamilo\CoreBundle\Settings\ProfileSettingsSchema;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\SettingsBundle\Schema\SettingsBuilder;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ProfileSettingsSchemaTest extends TestCase
{
    public function testDeprecatedUploadLayoutSettingIsHiddenButRemainsReadable(): void
    {
        $schema = new ProfileSettingsSchema();
        $settingsBuilder = new SettingsBuilder();
        $schema->buildSettings($settingsBuilder);

        // Old portals still need their saved value to resolve legacy user paths.
        $this->assertSame('true', $settingsBuilder->resolve([])['split_users_upload_directory']);
        $this->assertContains('split_users_upload_directory', $schema->getHiddenSettings());

        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findAll')->willReturn([]);
        $schema->setRepository($repository);
        $schema->setTranslator($this->createMock(TranslatorInterface::class));

        $addedFields = [];
        $formBuilder = $this->createMock(FormBuilderInterface::class);
        $formBuilder->method('all')->willReturn([]);
        $formBuilder->method('add')->willReturnCallback(
            static function ($name) use (&$addedFields, $formBuilder): FormBuilderInterface {
                $addedFields[] = $name;

                return $formBuilder;
            }
        );
        $schema->buildForm($formBuilder);

        $this->assertContains('extended_profile', $addedFields);
        $this->assertNotContains('split_users_upload_directory', $addedFields);
    }
}
