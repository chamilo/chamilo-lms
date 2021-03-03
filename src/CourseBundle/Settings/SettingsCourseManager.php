<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Settings;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\SettingsCurrent;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CCourseSetting;
use Sylius\Bundle\SettingsBundle\Model\Settings;
use Sylius\Bundle\SettingsBundle\Model\SettingsInterface;
use Sylius\Bundle\SettingsBundle\Schema\SchemaInterface;
use Sylius\Bundle\SettingsBundle\Schema\SettingsBuilder;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Exception\ValidatorException;

class SettingsCourseManager extends SettingsManager
{
    protected Course $course;

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

    public function load(string $schemaAlias, string $namespace = null, bool $ignoreUnknown = true): SettingsInterface
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
        if (true === $ignoreUnknown) {
            foreach ($parameters as $name => $value) {
                if (!$settingsBuilder->isDefined($name)) {
                    unset($parameters[$name]);
                }
            }
        }

        foreach ($settingsBuilder->getTransformers() as $parameter => $transformer) {
            if (array_key_exists($parameter, $parameters)) {
                $parameters[$parameter] = $transformer->reverseTransform($parameters[$parameter]);
            }
        }

        $parameters = $settingsBuilder->resolve($parameters);
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
            if (array_key_exists($parameter, $parameters)) {
                $parameters[$parameter] = $transformer->transform($parameters[$parameter]);
            }
        }

        $repo = $this->manager->getRepository(SettingsCurrent::class);
        /** @var CCourseSetting[] $persistedParameters */
        $persistedParameters = $repo->findBy([
            'category' => $namespace,
        ]);

        $persistedParametersMap = [];
        foreach ($persistedParameters as $parameter) {
            $persistedParametersMap[$parameter->getTitle()] = $parameter;
        }

        $simpleCategoryName = str_replace('chamilo_course.settings.', '', $namespace);
        foreach ($parameters as $name => $value) {
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

    /**
     * Load parameter from database.
     */
    private function getParameters(string $namespace): array
    {
        $repo = $this->manager->getRepository(CCourseSetting::class);
        $list = [];
        $parameters = $repo->findBy(['category' => $namespace]);
        foreach ($parameters as $parameter) {
            $list[$parameter->getTitle()] = $parameter->getValue();
        }

        return $list;
    }
}
