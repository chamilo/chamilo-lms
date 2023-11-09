<?php
declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class ContactType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('category', ChoiceType::class, [
                'choices' => [
                    'Question about OFAJ' => 'ofaj_question',
                    'Other' => 'other', // Add more categories as needed
                ],
                'label' => 'Category',
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('firstName', TextType::class, [
                'label' => 'First name',
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Last name',
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'E-mail',
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('subject', TextType::class, [
                'label' => 'Subject',
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('message', TextareaType::class, [
                'label' => 'Message',
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('termsAccepted', CheckboxType::class, [
                'mapped' => false,
                'label' => 'By checking this box, I confirm that I accept the data processing by OFAJ',
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Send'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        // You can define options here if needed
    }

    public function getName(): string
    {
        return 'contact';
    }
}
