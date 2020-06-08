<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class CurriculumItemRelUserType.
 */
class CurriculumItemRelUserType extends AbstractType
{
    public $itemId;

    /**
     * CurriculumItemRelUserType constructor.
     *
     * @param null $itemId
     */
    public function __construct($itemId = null)
    {
        $this->itemId = $itemId;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'description',
            'text',
            ['label' => ' ', 'attr' => ['class' => 'span7']]
        );
        $builder->add(
            'item_id',
            'hidden',
            ['attr' => ['value' => $this->itemId]]
        );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => 'Chamilo\CoreBundle\Entity\CurriculumItemRelUser',
            ]
        );
    }

    public function getName()
    {
        return 'curriculumItemRelUser';
    }
}
