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

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @final since sonata-project/block-bundle 3.0
 *
 * @author Hugo Briand <briand@ekino.com>
 */
class ContainerTemplateType extends AbstractType
{
    /**
     * @var array
     */
    protected $templateChoices;

    public function __construct(array $templateChoices)
    {
        $this->templateChoices = $templateChoices;
    }

    public function getBlockPrefix()
    {
        return 'sonata_type_container_template_choice';
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
        $resolver->setDefaults([
            'choices' => $this->templateChoices,
        ]);
    }
}
