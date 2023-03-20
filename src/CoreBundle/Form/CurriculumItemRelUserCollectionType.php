<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CurriculumItemRelUserCollectionType extends AbstractType
{
    /**
     * @var null
     */
    public $itemId;

    /**
     * CurriculumItemRelUserCollectionType constructor.
     *
     * @param null $itemId
     */
    public function __construct($itemId = null)
    {
        $this->itemId = $itemId;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'userItems',
            'collection',
            [
                'type' => new CurriculumItemRelUserType($this->itemId),
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'prototype' => true,
                'prototype_name' => '__name__',
                'options' => [// options on the rendered CurriculumItemRelUserType
                ],
                'label' => ' ',
            ]
        );

        // Save button per item
        //$builder->add('submit', 'submit', array('attr' => array('class' => 'btn btn--success', 'onclick' => 'save(this);')));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => 'Chamilo\CoreBundle\Entity\CurriculumItem',
            ]
        );
    }

    public function getName(): string
    {
        return 'CurriculumItemRelUserCollection';
    }
}
