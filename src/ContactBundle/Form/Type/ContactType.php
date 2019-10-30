<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\ContactBundle\Form\Type;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class ContactType.
 */
class ContactType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
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
            ->add('message', TextareaType::class)
            ->add('send', SubmitType::class, ['attr' => ['class' => 'btn btn-primary']])
        ;
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $collectionConstraint = new Collection([
            'category' => [
                new NotBlank(['message' => 'Category should not be blank.']),
            ],
            'firstname' => [
                new NotBlank(['message' => 'Firstname should not be blank.']),
                new Length(['min' => 2]),
            ],
            'lastname' => [
                new NotBlank(['message' => 'Lastname should not be blank.']),
                new Length(['min' => 2]),
            ],
            'email' => [
                new NotBlank(['message' => 'Email should not be blank.']),
                new Email(['message' => 'Invalid email address.']),
            ],
            'subject' => [
                new NotBlank(['message' => 'Subject should not be blank.']),
                new Length(['min' => 3]),
            ],
            'message' => [
                new NotBlank(['message' => 'Message should not be blank.']),
                new Length(['min' => 5]),
            ],
        ]);

        $resolver->setDefaults([
            'constraints' => $collectionConstraint,
        ]);
    }
}
