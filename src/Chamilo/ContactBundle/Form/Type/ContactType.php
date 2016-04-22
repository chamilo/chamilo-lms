<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\ContactBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

/**
 * Class ContactType
 * @package Chamilo\ContactBundle\Form\Type
 */
class ContactType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'category',
                EntityType::class,
                ['class' => 'Chamilo\ContactBundle\Entity\Category']
            )
            ->add('firstname')
            ->add('lastname')
            ->add('email')
            ->add('subject')
            ->add('message', 'textarea')
            ->add('send', SubmitType::class, ['attr' => ['class' => 'btn btn-primary']])
        ;
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $collectionConstraint = new Collection(array(
            'category' => array(
                new NotBlank(array('message' => 'Category should not be blank.'))
            ),
            'firstname' => array(
                new NotBlank(array('message' => 'firstname should not be blank.')),
                new Length(array('min' => 2))
            ),
            'lastname' => array(
                new NotBlank(array('message' => 'lastname should not be blank.')),
                new Length(array('min' => 2))
            ),
            'email' => array(
                new NotBlank(array('message' => 'Email should not be blank.')),
                new Email(array('message' => 'Invalid email address.'))
            ),
            'subject' => array(
                new NotBlank(array('message' => 'Subject should not be blank.')),
                new Length(array('min' => 3))
            ),
            'message' => array(
                new NotBlank(array('message' => 'Message should not be blank.')),
                new Length(array('min' => 5))
            )
        ));

        $resolver->setDefaults(array(
            'constraints' => $collectionConstraint
        ));
    }
}