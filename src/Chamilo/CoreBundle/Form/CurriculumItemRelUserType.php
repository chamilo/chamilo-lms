<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Entity;

/**
 * Class CurriculumItemRelUserType
 * @package Chamilo\CoreBundle\Form
 */
class CurriculumItemRelUserType extends AbstractType
{
    public $itemId;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('description', 'text', array('label' => ' ', 'attr' => array('class' => 'span7')));
        $builder->add('item_id', 'hidden', array('attr' => array('value' => $this->itemId)));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => 'Chamilo\CoreBundle\Entity\CurriculumItemRelUser',
            )
        );
    }

    public function getName()
    {
        return 'curriculumItemRelUser';
    }

    public function __construct($itemId = null)
    {
        $this->itemId = $itemId;
    }
}
