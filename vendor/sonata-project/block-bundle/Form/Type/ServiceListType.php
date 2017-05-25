<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\BlockBundle\Form\Type;

use Sonata\BlockBundle\Block\BlockServiceManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ServiceListType extends AbstractType
{
    protected $manager;

    /**
     * @param BlockServiceManagerInterface $manager
     */
    public function __construct(BlockServiceManagerInterface $manager)
    {
        $this->manager = $manager;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'sonata_block_service_choice';
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
     */
    public function getParent()
    {
        return 'choice';
    }

    /**
     * {@inheritdoc}
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
        $manager = $this->manager;

        $resolver->setRequired(array(
            'context',
        ));

        $resolver->setDefaults(array(
            'multiple' => false,
            'expanded' => false,
            'choices' => function (Options $options, $previousValue) use ($manager) {
                $types = array();
                foreach ($manager->getServicesByContext($options['context'], $options['include_containers']) as $code => $service) {
                    $types[$code] = sprintf('%s - %s', $service->getName(), $code);
                }

                return $types;
            },
            'preferred_choices' => array(),
            'empty_data' => function (Options $options) {
                $multiple = isset($options['multiple']) && $options['multiple'];
                $expanded = isset($options['expanded']) && $options['expanded'];

                return $multiple || $expanded ? array() : '';
            },
            'empty_value' => function (Options $options, $previousValue) {
                $multiple = isset($options['multiple']) && $options['multiple'];
                $expanded = isset($options['expanded']) && $options['expanded'];

                return $multiple || $expanded || !isset($previousValue) ? null : '';
            },
            'error_bubbling' => false,
            'include_containers' => false,
        ));
    }
}
