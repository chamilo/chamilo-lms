<?php

declare(strict_types=1);

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
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @final since sonata-project/block-bundle 3.0
 */
class ServiceListType extends AbstractType
{
    protected $manager;

    public function __construct(BlockServiceManagerInterface $manager)
    {
        $this->manager = $manager;
    }

    public function getBlockPrefix()
    {
        return 'sonata_block_service_choice';
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    public function getParent()
    {
        return ChoiceType::class;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $manager = $this->manager;

        $resolver->setRequired([
            'context',
        ]);

        $resolver->setDefaults([
            'multiple' => false,
            'expanded' => false,
            'choices' => static function (Options $options, $previousValue) use ($manager) {
                $types = [];
                foreach ($manager->getServicesByContext($options['context'], $options['include_containers']) as $code => $service) {
                    $types[$code] = sprintf('%s - %s', $service->getName(), $code);
                }

                return $types;
            },
            'preferred_choices' => [],
            'empty_data' => static function (Options $options) {
                $multiple = isset($options['multiple']) && $options['multiple'];
                $expanded = isset($options['expanded']) && $options['expanded'];

                return $multiple || $expanded ? [] : '';
            },
            'empty_value' => static function (Options $options, $previousValue) {
                $multiple = isset($options['multiple']) && $options['multiple'];
                $expanded = isset($options['expanded']) && $options['expanded'];

                return $multiple || $expanded || !isset($previousValue) ? null : '';
            },
            'error_bubbling' => false,
            'include_containers' => false,
        ]);
    }
}
