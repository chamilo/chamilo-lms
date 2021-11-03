<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Form;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Form\Type\IllustrationType;
use Chamilo\CoreBundle\Repository\LanguageRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\LocaleType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimezoneType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProfileType extends AbstractType
{
    private LanguageRepository $languageRepository;

    public function __construct(LanguageRepository $languageRepository)
    {
        $this->languageRepository = $languageRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $languages = array_flip($this->languageRepository->getAllAvailableToArray());

        $builder
            ->add('firstname', TextType::class, ['label' => 'Firstname', 'required' => true])
            ->add('lastname', TextType::class, ['label' => 'Lastname', 'required' => true])
            ->add('email', EmailType::class, ['label' => 'Email', 'required' => true])
            //->add('official_code', TextType::class)
            //->add('groups')
            ->add('locale', LocaleType::class, [
                //'preferred_choices' => ['en', 'fr_FR', 'es_ES', 'pt', 'nl'],
                'choices' => $languages,
                'choice_loader' => null,
            ])
            /*->add(                'dateOfBirth',
                BirthdayType::class,
                [
                    'label' => 'form.label_date_of_birth',
                    'required' => false,
                    'widget' => 'single_text',
                ]
            )
            ->add(
                'biography',
                TextareaType::class,
                [
                    'label' => 'form.label_biography',
                    'required' => false,
                ]
            )*/
            /*->add('locale', 'locale', array(
                'label'    => 'form.label_locale',
                'required' => false,
            ))*/
            ->add('timezone', TimezoneType::class, ['label' => 'Timezone', 'required' => true])
            ->add('phone', TextType::class, ['label' => 'Phone number', 'required' => false])
            ->add(
                'illustration',
                IllustrationType::class,
                ['label' => 'Picture', 'required' => false, 'mapped' => false]
            )
            //->add('website', UrlType::class, ['label' => 'Website', 'required' => false])
        ;

        $builder->add('extra_fields', ExtraFieldType::class, ['mapped' => false]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => User::class,
            ]
        );
    }
}
