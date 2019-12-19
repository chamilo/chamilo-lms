<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class IllustrationType.
 */
class IllustrationType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        /*$resolver->setDefaults(
            [
                'choices' => [
                    'Yes' => 'true',
                    'No' => 'false',
                ],
            ]
        );*/
    }

    public function getParent()
    {
        return FileType::class;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'illustration';
    }
}
