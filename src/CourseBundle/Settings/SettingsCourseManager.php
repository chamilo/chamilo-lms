<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Settings;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Settings\CourseSettingPolicyManager;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CCourseSetting;
use Sylius\Bundle\SettingsBundle\Model\Settings;
use Sylius\Bundle\SettingsBundle\Model\SettingsInterface;
use Sylius\Bundle\SettingsBundle\Schema\SchemaInterface;
use Sylius\Bundle\SettingsBundle\Schema\SettingsBuilder;

class SettingsCourseManager extends SettingsManager
{
    protected Course $course;

    private ?CourseSettingPolicyManager $courseSettingPolicyManager = null;

    public function setCourseSettingPolicyManager(CourseSettingPolicyManager $policyManager): void
    {
        $this->courseSettingPolicyManager = $policyManager;
    }

    /**
     * @return Course
     */
    public function getCourse()
    {
        return $this->course;
    }

    public function setCourse(Course $course): void
    {
        $this->course = $course;
    }

    public function load(string $schemaAlias, ?string $namespace = null, bool $ignoreUnknown = true): SettingsInterface
    {
        $settings = new Settings();
        $schemaAliasNoPrefix = $schemaAlias;
        $schemaAlias = 'chamilo_course.settings.'.$schemaAlias;
        if (!$this->schemaRegistry->has($schemaAlias)) {
            return $settings;
        }

        /** @var SchemaInterface $schema */
        $schema = $this->schemaRegistry->get($schemaAlias);
        $settings->setSchemaAlias($schemaAlias);

        // We need to get a plain parameters array since we use the options resolver on it
        $parameters = $this->getParameters($schemaAliasNoPrefix);
        $settingsBuilder = new SettingsBuilder();
        $schema->buildSettings($settingsBuilder);

        // Remove unknown settings' parameters (e.g. From a previous version of the settings schema)
        if ($ignoreUnknown) {
            foreach (array_keys($parameters) as $name) {
                if (!$settingsBuilder->isDefined($name)) {
                    unset($parameters[$name]);
                }
            }
        }

        foreach ($settingsBuilder->getTransformers() as $parameter => $transformer) {
            if (\array_key_exists($parameter, $parameters)) {
                $parameters[$parameter] = $transformer->reverseTransform($parameters[$parameter]);
            }
        }

        $storedParameters = $parameters;
        $parameters = $settingsBuilder->resolve($parameters);
        if (null !== $this->courseSettingPolicyManager) {
            foreach ($parameters as $name => $defaultValue) {
                $effective = $this->courseSettingPolicyManager->effectiveValue(
                    $name,
                    $storedParameters[$name] ?? null,
                    $defaultValue,
                    $schemaAliasNoPrefix,
                );
                if (\is_int($defaultValue) && is_numeric((string) $effective)) {
                    $effective = (int) $effective;
                }
                $parameters[$name] = $effective;
            }
        }
        $settings->setParameters($parameters);

        return $settings;
    }

    public function save(SettingsInterface $settings): void
    {
        $namespace = $settings->getSchemaAlias();

        /** @var SchemaInterface $schema */
        $schema = $this->schemaRegistry->get($namespace);

        $settingsBuilder = new SettingsBuilder();
        $schema->buildSettings($settingsBuilder);
        $parameters = $settingsBuilder->resolve($settings->getParameters());

        // Transform value. Example array to string using transformer. Example:
        // 1. Setting "tool_visible_by_default_at_creation" it's a multiple select
        // 2. Is defined as an array in class DocumentSettingsSchema
        // 3. Add transformer for that variable "ArrayToIdentifierTransformer"
        // 4. Here we recover the transformer and convert the array to string
        foreach ($settingsBuilder->getTransformers() as $parameter => $transformer) {
            if (\array_key_exists($parameter, $parameters)) {
                $parameters[$parameter] = $transformer->transform($parameters[$parameter]);
            }
        }

        $simpleCategoryName = str_replace('chamilo_course.settings.', '', $namespace);
        $repo = $this->manager->getRepository(CCourseSetting::class);

        /** @var CCourseSetting[] $persistedParameters */
        $persistedParameters = $repo->findBy([
            'cId' => (int) $this->getCourse()->getId(),
            'category' => $simpleCategoryName,
        ]);

        $persistedParametersMap = [];
        foreach ($persistedParameters as $parameter) {
            $persistedParametersMap[$parameter->getVariable()] = $parameter;
        }

        foreach ($parameters as $name => $value) {
            if (null !== $this->courseSettingPolicyManager
                && !$this->courseSettingPolicyManager->isEditable($name, $simpleCategoryName)) {
                continue;
            }

            $value = match (true) {
                \is_bool($value) => $value ? 'true' : 'false',
                null === $value => '',
                default => (string) $value,
            };

            if (isset($persistedParametersMap[$name])) {
                $persistedParametersMap[$name]->setValue($value);
            } else {
                $parameter = new CCourseSetting();
                $parameter
                    ->setTitle($name)
                    ->setVariable($name)
                    ->setCategory($simpleCategoryName)
                    ->setValue($value)
                    ->setCId($this->getCourse()->getId())
                ;
                $this->manager->persist($parameter);
            }
        }

        $this->manager->flush();
    }

    public function convertNameSpaceToService(string $category): string
    {
        return 'chamilo_course.settings.'.$category;
    }

    public function convertServiceToNameSpace(string $category): string
    {
        return str_replace('chamilo_course.settings.', '', $category);
    }

    /**
     * Load parameter from database.
     */
    private function getParameters(string $namespace): array
    {
        $repo = $this->manager->getRepository(CCourseSetting::class);
        $list = [];
        $parameters = $repo->findBy([
            'cId' => (int) $this->getCourse()->getId(),
            'category' => $namespace,
        ]);
        foreach ($parameters as $parameter) {
            $list[$parameter->getVariable()] = $parameter->getValue();
        }

        return $list;
    }

    /**
     * Fetches the value of a specific setting for the given course and variable.
     *
     * @param string $variableName the name of the variable to fetch
     *
     * @return string|null the value of the setting if found, or null if not
     */
    public function getCourseSettingValue(string $variableName, ?string $category = null): ?string
    {
        $repo = $this->manager->getRepository(CCourseSetting::class);
        $criteria = [
            'cId' => $this->course->getId(),
            'variable' => $variableName,
        ];
        if (null !== $category && '' !== $category) {
            $criteria['category'] = $category;
        }
        $courseSetting = $repo->findOneBy($criteria);

        $value = $courseSetting?->getValue();
        if (null === $this->courseSettingPolicyManager) {
            return $value;
        }

        $resolvedCategory = $category ?? $courseSetting?->getCategory();
        $effective = $this->courseSettingPolicyManager->effectiveValue($variableName, $value, null, $resolvedCategory);

        return null === $effective ? null : (string) $effective;
    }
}
