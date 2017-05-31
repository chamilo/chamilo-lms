<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CoreBundle\Form;

use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FormHelper
{
    private static $typeMapping = array();

    private static $extensionMapping = array();

    /**
     * This function remove fields available if there are not present in the $data array
     * The data array might come from $request->request->all().
     *
     * This can be usefull if you don't want to send all fields will building an api. As missing
     * fields will be threated like null values.
     *
     * @param array $data
     * @param Form  $form
     */
    public static function removeFields(array $data, Form $form)
    {
        $diff = array_diff(array_keys($form->all()), array_keys($data));

        foreach ($diff as $key) {
            $form->remove($key);
        }

        foreach ($data as $name => $value) {
            if (!is_array($value)) {
                continue;
            }

            self::removeFields($value, $form[$name]);
        }
    }

    /**
     * @return array
     */
    public static function getFormExtensionMapping()
    {
        return self::$extensionMapping;
    }

    /**
     * @param array $mapping
     */
    public static function registerFormTypeMapping(array $mapping)
    {
        self::$typeMapping = array_merge(self::$typeMapping, $mapping);
    }

    /**
     * @param string $type
     * @param array  $services
     */
    public static function registerFormExtensionMapping($type, array $services)
    {
        if (!isset(self::$extensionMapping[$type])) {
            self::$extensionMapping[$type] = array();
        }

        self::$extensionMapping[$type] = array_merge(self::$extensionMapping[$type], $services);
    }

    /**
     * @return array
     */
    public static function getFormTypeMapping()
    {
        return self::$typeMapping;
    }

    /**
     * @param FormTypeInterface $type
     * @param OptionsResolver   $optionsResolver
     *
     * @internal
     */
    public static function configureOptions(FormTypeInterface $type, OptionsResolver $optionsResolver)
    {
        if (!method_exists('Symfony\Component\Form\AbstractType', 'getBlockPrefix')) {
            $type->setDefaultOptions($optionsResolver);
        } else { // SF <2.8 BC
            $type->configureOptions($optionsResolver);
        }
    }
}
