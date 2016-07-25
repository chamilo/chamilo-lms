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
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ImmutableArrayType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        foreach ($options['keys'] as $infos) {
            if ($infos instanceof FormBuilderInterface) {
                $builder->add($infos);
            } else {
                list($name, $type, $options) = $infos;

                if (is_callable($options)) {
                    $extra = array_slice($infos, 3);

                    $options = $options($builder, $name, $type, $extra);

                    if ($options === null) {
                        $options = array();
                    } elseif (!is_array($options)) {
                        throw new \RuntimeException('the closure must return null or an array');
                    }
                }

                $builder->add($name, $type, $options);
            }
        }
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
        $resolver->setDefaults(array(
            'keys'    => array(),
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'sonata_type_immutable_array';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }
}
