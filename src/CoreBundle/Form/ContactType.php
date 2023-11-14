<?php
declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Form;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Chamilo\CoreBundle\Entity\ContactCategory;

class ContactType extends AbstractType
{
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('category', EntityType::class, [
                'class' => ContactCategory::class,
                'choice_label' => 'name',
                'label' => $this->translator->trans('Category'),
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('firstName', TextType::class, [
                'label' => $this->translator->trans('First name'),
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('lastName', TextType::class, [
                'label' => $this->translator->trans('Last name'),
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => $this->translator->trans('E-mail'),
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('subject', TextType::class, [
                'label' => $this->translator->trans('Subject'),
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('message', TextareaType::class, [
                'label' => $this->translator->trans('Message'),
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('termsAccepted', CheckboxType::class, [
                'mapped' => false,
                'label' => $this->translator->trans('By checking this box, I confirm that I accept the data processing by OFAJ'),
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('terms', TextareaType::class, [
                'mapped' => false,
                'label' => '',
                'data' => $options['terms_content'],
                'attr' => [
                    'disabled' => true,
                    'class' => 'text-gray-500',
                    'style' => 'width: 100%;height: 180px;',
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => $this->translator->trans('Send'),
                'attr' => [
                    'class' => 'btn btn--primary hover:bg-blue-700 text-white font-bold py-2 px-4 rounded cursor-pointer',
                    'style' => 'border: none;',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        // You can define options here if needed
        $resolver->setDefaults([
            'terms_content' => $this->translator->trans('OFAJ, responsable du traitement, met en œuvre un traitement de données à caractère personnel pour répondre à votre demande de création d’un compte utilisateur pour accéder à la plateforme de formation'),
        ]);
    }

    public function getName(): string
    {
        return 'contact';
    }
}
