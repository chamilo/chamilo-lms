<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class YesNoNumericType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'choices' => [
                    'Yes' => 1,
                    'No' => 0,
                ],
            ]
        );
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer(
            new CallbackTransformer(
                function ($value) {
                    return (int) $value;
                },
                function ($value) {
                    return (string) $value;
                }
            )
        );
    }

    public function getParent()
    {
        return ChoiceType::class;
    }
}
