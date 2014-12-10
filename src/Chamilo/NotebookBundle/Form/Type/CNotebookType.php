<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\NotebookBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class CNotebookType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name')
            ->add('description', 'ckeditor')
            /*->add('cId')
            ->add('notebookId')
            ->add('userId')
            ->add('course')
            ->add('sessionId')*/
            ->add('save', 'submit');
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Chamilo\NotebookBundle\Entity\CNotebook'
        ));
    }

    public function getName()
    {
        return 'chamilo_notebook';
    }
}
