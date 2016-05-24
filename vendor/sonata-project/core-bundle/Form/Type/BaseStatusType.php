<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CoreBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

abstract class BaseStatusType extends AbstractType
{
    /**
     * @var string
     */
    protected $class;

    /**
     * @var string
     */
    protected $getter;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var bool
     */
    protected $flip;

    /**
     * @param string $class
     * @param string $getter
     * @param string $name
     * @param bool   $flip   reverse key/value to match sf2.8 and sf3.0 change
     */
    public function __construct($class, $getter, $name, $flip = false)
    {
        $this->class  = $class;
        $this->getter = $getter;
        $this->name   = $name;
        $this->flip   = $flip;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'choice';
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     *
     * @todo Remove it when bumping requirements to SF 2.7+
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $this->configureOptions($resolver);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $choices = call_user_func(array($this->class, $this->getter));

        if ($this->flip) {
            $count = count($choices);

            $choices = array_flip($choices);

            if (count($choices) !== $count) {
                throw new \RuntimeException('Unable to safely flip value as final count is different');
            }
        }

        $resolver->setDefaults(array(
            'choices' => $choices,
        ));
    }
}
