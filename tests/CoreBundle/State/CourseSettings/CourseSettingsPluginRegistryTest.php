<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\State\CourseSettings;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Settings\CourseSettingPolicyManager;
use Chamilo\CoreBundle\State\CourseSettings\CourseSettingsPluginInterface;
use Chamilo\CoreBundle\State\CourseSettings\CourseSettingsPluginRegistry;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class CourseSettingsPluginRegistryTest extends TestCase
{
    public function testThirdPartySectionIsDiscoveredAndSubmittedFieldsAreAllowlisted(): void
    {
        $plugin = $this->plugin();
        $registry = $this->registry([$plugin], $this->policy());
        $course = new Course();

        $this->assertTrue($registry->getIntegrations($course, null)['example']['enabled']);
        $this->assertSame('example_section', $registry->getSections($course, null, [])[0]['key']);
        $this->assertSame(['example_note' => 'Previous'], $registry->getValues($course, null));

        $saved = $registry->save($course, null, [
            'example_note' => 'New value',
            'title' => 'Injected title',
        ], ['example_note' => 'Previous']);
        $this->assertSame(['example_note' => 'New value'], $saved);
        $this->assertSame(['example_note' => 'New value'], $plugin->saved);
        $registry->afterSave($course, null, $saved);
        $this->assertSame($saved, $plugin->afterSaved);
    }

    public function testPartialPayloadPreservesOtherDeclaredSettings(): void
    {
        $plugin = $this->plugin();
        $plugin->fields[] = ['key' => 'example_mode', 'label' => 'Mode', 'type' => 'radio', 'options' => [
            ['label' => 'Off', 'value' => 'disabled'],
            ['label' => 'On', 'value' => 'enabled'],
        ]];
        $registry = $this->registry([$plugin], $this->policy());

        $registry->save(new Course(), null, ['example_note' => 'Updated'], [
            'example_note' => 'Previous',
            'example_mode' => 'enabled',
        ]);

        $this->assertSame([
            'example_note' => 'Updated',
            'example_mode' => 'enabled',
        ], $plugin->saved);
    }

    public function testDisabledPluginsNeitherRenderNorWrite(): void
    {
        $plugin = $this->plugin(false);
        $registry = $this->registry([$plugin], $this->policy());
        $course = new Course();

        $this->assertSame([], $registry->getSections($course, null, []));
        $this->assertSame([], $registry->save($course, null, ['example_note' => 'Changed'], []));
        $this->assertSame([], $plugin->saved);
    }

    public function testCoursePoliciesFilterFieldsAndBlockWrites(): void
    {
        $plugin = $this->plugin();
        $registry = $this->registry([$plugin], $this->policy([
            'example_note' => ['editable' => false, 'default' => 'Locked'],
        ]));
        $course = new Course();

        $this->assertSame([], $registry->getSections($course, null, [])[0]['fields']);
        $this->assertSame([], $registry->save($course, null, ['example_note' => 'Override'], []));
        $this->assertSame([], $plugin->saved);
    }

    public function testInvalidPluginSelectValueIsRejectedBeforePersistence(): void
    {
        $plugin = $this->plugin();
        $plugin->fields = [
            ['key' => 'example_note', 'label' => 'Notification', 'type' => 'select', 'options' => [
                ['label' => 'None', 'value' => 0],
                ['label' => 'Enabled', 'value' => 1],
            ]],
        ];
        $registry = $this->registry([$plugin], $this->policy());

        $this->expectException(BadRequestHttpException::class);
        $registry->save(new Course(), null, ['example_note' => 'unexpected'], []);
    }

    public function testDuplicateIntegrationNamesAreRejected(): void
    {
        $plugin1 = $this->plugin();
        $plugin2 = $this->plugin();
        $registry = $this->registry([$plugin1, $plugin2], $this->policy());

        $this->expectException(InvalidArgumentException::class);
        $registry->getIntegrations(new Course(), null);
    }

    /**
     * @param list<object> $plugins
     */
    private function registry(array $plugins, CourseSettingPolicyManager $policies): CourseSettingsPluginRegistry
    {
        $typed = [];
        foreach ($plugins as $plugin) {
            $this->assertInstanceOf(CourseSettingsPluginInterface::class, $plugin);
            $typed[] = $plugin;
        }

        return new CourseSettingsPluginRegistry($typed, $policies);
    }

    private function policy(array $policies = []): CourseSettingPolicyManager
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $manager = new CourseSettingPolicyManager($entityManager);
        $property = new ReflectionProperty(CourseSettingPolicyManager::class, 'cachedPolicies');
        $property->setValue($manager, $policies);

        return $manager;
    }

    private function plugin(bool $enabled = true): object
    {
        return new class($enabled) implements CourseSettingsPluginInterface {
            public array $saved = [];
            public array $afterSaved = [];
            public array $fields = [
                ['key' => 'example_note', 'label' => 'Note', 'type' => 'text'],
            ];

            public function __construct(
                private readonly bool $enabled
            ) {}

            public function getIntegrationKey(): string
            {
                return 'example';
            }

            public function getIntegration(Course $course, ?Session $session): array
            {
                return ['enabled' => $this->enabled];
            }

            public function getSection(Course $course, ?Session $session, array $values): ?array
            {
                return [
                    'key' => 'example_section',
                    'title' => 'Example',
                    'icon' => 'settings',
                    'fields' => $this->fields,
                ];
            }

            public function getValues(Course $course, ?Session $session): array
            {
                return ['example_note' => 'Previous'];
            }

            public function saveValues(Course $course, ?Session $session, array $values): void
            {
                $this->saved = $values;
            }

            public function afterSave(Course $course, ?Session $session, array $values): void
            {
                $this->afterSaved = $values;
            }

            public function managesCourseSetting(string $variable): bool
            {
                return false;
            }
        };
    }
}
