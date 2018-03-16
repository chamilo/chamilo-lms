<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Manager;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CourseBundle\Entity\CCourseSetting;
use Chamilo\SettingsBundle\Manager\SettingsManager as ChamiloSettingsManager;
use Sylius\Bundle\SettingsBundle\Model\SettingsInterface;
use Sylius\Bundle\SettingsBundle\Schema\SchemaInterface;
use Sylius\Bundle\SettingsBundle\Schema\SettingsBuilder;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Exception\ValidatorException;

/**
 * Class SettingsManager
 * Course settings manager.
 *
 * @package Chamilo\CourseBundle\Manager
 */
class SettingsManager extends ChamiloSettingsManager
{
    protected $course;

    /**
     * @return Course
     */
    public function getCourse()
    {
        return $this->course;
    }

    /**
     * @param Course $course
     */
    public function setCourse(Course $course)
    {
        $this->course = $course;
    }

    /**
     * {@inheritdoc}
     */
    public function load($schemaAlias, $namespace = null, $ignoreUnknown = true)
    {
        $schemaAliasNoPrefix = $schemaAlias;
        $schemaAlias = 'chamilo_core.settings.'.$schemaAlias;

        if ($this->schemaRegistry->has($schemaAlias)) {
            /** @var SchemaInterface $schema */
            $schema = $this->schemaRegistry->get($schemaAlias);
        } else {
            return [];
        }

        /** @var \Sylius\Bundle\SettingsBundle\Model\Settings $settings */
        $settings = $this->settingsFactory->createNew();
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

    /**
     * {@inheritdoc}
     */
    public function save(SettingsInterface $settings)
    {
        $namespace = $settings->getSchemaAlias();

        /** @var SchemaInterface $schema */
        $schema = $this->schemaRegistry->get($settings->getSchemaAlias());

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

        $repo = $this->manager->getRepository('ChamiloCoreBundle:SettingsCurrent');
        $persistedParameters = $repo->findBy(['category' => $settings->getSchemaAlias()]);
        $persistedParametersMap = [];

        foreach ($persistedParameters as $parameter) {
            $persistedParametersMap[$parameter->getTitle()] = $parameter;
        }

        /** @var \Chamilo\CoreBundle\Entity\SettingsCurrent $url */
        //$url = $event->getArgument('url');
        $url = $this->getUrl();

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

        return;

        $schema = $this->schemaRegistry->getSchema($namespace);

        $settingsBuilder = new SettingsBuilder();
        $schema->buildSettings($settingsBuilder);

        $parameters = $settingsBuilder->resolve($settings->getParameters());

        foreach ($settingsBuilder->getTransformers() as $parameter => $transformer) {
            if (array_key_exists($parameter, $parameters)) {
                $parameters[$parameter] = $transformer->transform($parameters[$parameter]);
            }
        }

        if (isset($this->resolvedSettings[$namespace])) {
            $this->resolvedSettings[$namespace]->setParameters($parameters);
        }

        $persistedParameters = $this->parameterRepository->findBy(
            ['category' => $namespace, 'cId' => $this->getCourse()->getId()]
        );

        $persistedParametersMap = [];

        foreach ($persistedParameters as $parameter) {
            $persistedParametersMap[$parameter->getName()] = $parameter;
        }

        foreach ($parameters as $name => $value) {
            if (isset($persistedParametersMap[$name])) {
                $persistedParametersMap[$name]->setValue($value);
            } else {
                /** @var CCourseSetting $parameter */
                //$parameter = $this->parameterFactory->createNew();
                $parameter = new CCourseSetting();
                $parameter
                    ->setNamespace($namespace)
                    ->setName($name)
                    ->setValue($value)
                    ->setCId($this->getCourse()->getId())
                ;

                /* @var $errors ConstraintViolationListInterface */
                $errors = $this->validator->validate($parameter);
                if (0 < $errors->count()) {
                    throw new ValidatorException($errors->get(0)->getMessage());
                }

                $this->parameterManager->persist($parameter);
            }
        }

        $this->parameterManager->flush();

        $this->cache->save($namespace, $parameters);
    }

    /**
     * @param string $category
     *
     * @return string
     */
    public function convertNameSpaceToService($category)
    {
        return 'chamilo_course.settings.'.$category;
    }

    /**
     * Load parameter from database.
     *
     * @param string $namespace
     *
     * @return array
     */
    private function getParameters($namespace)
    {
        $repo = $this->manager->getRepository('ChamiloCourseBundle:CCourseSetting');
        $parameters = [];
        foreach ($repo->findBy(['category' => $namespace]) as $parameter) {
            $parameters[$parameter->getTitle()] = $parameter->getValue();
        }

        return $parameters;
    }
}
