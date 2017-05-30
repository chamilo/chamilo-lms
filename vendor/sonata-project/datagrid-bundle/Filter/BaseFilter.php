<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\DatagridBundle\Filter;

abstract class BaseFilter implements FilterInterface
{
    /**
     * @var string
     */
    protected $name = null;

    /**
     * @var mixed
     */
    protected $value = null;

    /**
     * @var array
     */
    protected $options = array();

    /**
     * @var string
     */
    protected $condition;

    /**
     * {@inheritdoc}
     */
    public function initialize($name, array $options = array())
    {
        $this->name = $name;
        $this->setOptions($options);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function getFormName()
    {
        /* Symfony default form class sadly can't handle
           form element with dots in its name (when data
           get bound, the default dataMapper is a PropertyPathMapper).
           So use this trick to avoid any issue.
        */

        return str_replace('.', '__', $this->name);
    }

    /**
     * {@inheritdoc}
     */
    public function getOption($name, $default = null)
    {
        if (array_key_exists($name, $this->options)) {
            return $this->options[$name];
        }

        return $default;
    }

    /**
     * {@inheritdoc}
     */
    public function setOption($name, $value)
    {
        $this->options[$name] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldType()
    {
        return $this->getOption('field_type', 'text');
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldOptions()
    {
        return $this->getOption('field_options', array('required' => false));
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return $this->getOption('label');
    }

    /**
     * {@inheritdoc}
     */
    public function setLabel($label)
    {
        $this->setOption('label', $label);
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldName()
    {
        $fieldName = $this->getOption('field_name');

        if (!$fieldName) {
            throw new \RuntimeException(sprintf('The option `field_name` must be set for field : `%s`', $this->getName()));
        }

        return $fieldName;
    }

    /**
     * @param array $options
     */
    public function setOptions(array $options)
    {
        $this->options = array_merge($this->getDefaultOptions(), $options);
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param mixed $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * {@inheritdoc}
     */
    public function isActive()
    {
        $values = $this->getValue();

        return isset($values['value'])
        && false !== $values['value']
        && '' !== $values['value'];
    }

    /**
     * @param string $condition
     */
    public function setCondition($condition)
    {
        $this->condition = $condition;
    }

    /**
     * {@inheritdoc}
     */
    public function getCondition()
    {
        return $this->condition;
    }

    /**
     * {@inheritdoc}
     */
    public function getTranslationDomain()
    {
        return $this->getOption('translation_domain');
    }
}
