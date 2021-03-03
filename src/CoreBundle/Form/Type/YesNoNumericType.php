<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Form\Type;

use Chamilo\CoreBundle\Form\DataTransformer\ResourceToIdentifierTransformer;
use Mpdf\Tag\P;
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

                    $value = (int) $value;

                    return $value;
                },
                function ($value) {
                    $value = (string) $value;

                    return $value;
                }
            )
        );
    }

    public function getParent()
    {
        return ChoiceType::class;
    }

    public function getName(): string
    {
        return 'yes_no_numeric';
    }
}
