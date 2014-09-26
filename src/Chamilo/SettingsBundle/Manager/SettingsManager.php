<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\SettingsBundle\Manager;

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Persistence\ObjectManager;
use Sylius\Bundle\SettingsBundle\Model\Settings;
use Sylius\Bundle\SettingsBundle\Schema\SchemaRegistryInterface;
use Sylius\Bundle\SettingsBundle\Schema\SettingsBuilder;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Exception\ValidatorException;
use Symfony\Component\Validator\ValidatorInterface;
use Sylius\Bundle\SettingsBundle\Manager\SettingsManager as SyliusSettingsManager;
use Chamilo\CoreBundle\Entity\SettingsCurrent;

/**
 * Class SettingsManager
 * @package Chamilo\SettingsBundle\Manager
 */
class SettingsManager extends SyliusSettingsManager
{
    /**
     *
     */
    public function installSchemas()
    {
        $schemas = $this->getSchemas();
        $schemas = array_keys($schemas);
        /**
         * @var string $key
         * @var \Sylius\Bundle\SettingsBundle\Schema\SchemaInterface $schema
         */
        foreach ($schemas as $schema) {
            $settings = $this->loadSettings($schema);
            $this->saveSettings($schema, $settings);
        }
    }

    /**
     * @return array
     */
    public function getSchemas()
    {
        return $this->schemaRegistry->getSchemas();
    }

    /**
     * @param $name
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function getSetting($name)
    {
        if (false === strpos($name, '.')) {
            throw new \InvalidArgumentException(sprintf('Parameter must be in format "namespace.name", "%s" given.', $name));
        }

        list($namespace, $name) = explode('.', $name);

        $settings = $this->loadSettings($namespace);

        return $settings->get($name);
    }

    /**
     * {@inheritdoc}
     */
    public function loadSettings($namespace)
    {
        if (isset($this->resolvedSettings[$namespace])) {
            return $this->resolvedSettings[$namespace];
        }

        if ($this->cache->contains($namespace)) {
            $parameters = $this->cache->fetch($namespace);
        } else {
            $parameters = $this->getParameters($namespace);
        }

        $schema = $this->schemaRegistry->getSchema($namespace);

        $settingsBuilder = new SettingsBuilder();
        $schema->buildSettings($settingsBuilder);

        foreach ($settingsBuilder->getTransformers() as $parameter => $transformer) {
            if (array_key_exists($parameter, $parameters)) {
                $parameters[$parameter] = $transformer->reverseTransform($parameters[$parameter]);
            }
        }

        $parameters = $settingsBuilder->resolve($parameters);

        return $this->resolvedSettings[$namespace] = new Settings($parameters);
    }

    /**
     * {@inheritdoc}
     * @throws ValidatorException
     */
    public function saveSettings($namespace, Settings $settings)
    {
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

        $persistedParameters = $this->parameterRepository->findBy(array('category' => $namespace));
        $persistedParametersMap = array();

        foreach ($persistedParameters as $parameter) {
            $persistedParametersMap[$parameter->getName()] = $parameter;
        }

        foreach ($parameters as $name => $value) {
            if (isset($persistedParametersMap[$name])) {
                $persistedParametersMap[$name]->setValue($value);
            } else {
                /** @var SettingsCurrent $parameter */
                $parameter = $this->parameterRepository->createNew();

                $parameter
                    ->setNamespace($namespace)
                    ->setName($name)
                    ->setValue($value)
                    ->setAccessUrlChangeable(1)
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
     * Load parameter from database.
     *
     * @param string $namespace
     *
     * @return array
     */
    private function getParameters($namespace)
    {
        $parameters = array();

        foreach ($this->parameterRepository->findBy(array('category' => $namespace)) as $parameter) {
            $parameters[$parameter->getName()] = $parameter->getValue();
        }

        return $parameters;
    }
}
