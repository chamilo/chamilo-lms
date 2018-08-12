<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\UserBundle\Form\Type;

use Sonata\UserBundle\Model\UserInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ProfileType extends AbstractType
{
    /**
     * @var string
     */
    private $class;

    /**
     * @param string $class The User class name
     */
    public function __construct($class)
    {
        $this->class = $class;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // NEXT_MAJOR: Keep FQCN when bumping Symfony requirement to 2.8+.
        if (method_exists('Symfony\Component\Form\AbstractType', 'getBlockPrefix')) {
            $userGenderType = 'Sonata\UserBundle\Form\Type\UserGenderListType';
            $birthdayType = 'Symfony\Component\Form\Extension\Core\Type\BirthdayType';
            $urlType = 'Symfony\Component\Form\Extension\Core\Type\UrlType';
            $textareaType = 'Symfony\Component\Form\Extension\Core\Type\TextareaType';
            $localeType = 'Symfony\Component\Form\Extension\Core\Type\LocaleType';
            $timezoneType = 'Symfony\Component\Form\Extension\Core\Type\TimezoneType';
        } else {
            $userGenderType = 'sonata_user_gender';
            $birthdayType = 'birthday';
            $urlType = 'url';
            $textareaType = 'text';
            $localeType = 'locale';
            $timezoneType = 'timezone';
        }

        $builder
            ->add('gender', $userGenderType, [
                'label' => 'form.label_gender',
                'required' => true,
                'translation_domain' => 'SonataUserBundle',
                'choices' => [
                    UserInterface::GENDER_FEMALE => 'gender_female',
                    UserInterface::GENDER_MALE => 'gender_male',
                ],
            ])
            ->add('firstname', null, [
                'label' => 'form.label_firstname',
                'required' => false,
            ])
            ->add('lastname', null, [
                'label' => 'form.label_lastname',
                'required' => false,
            ])
            ->add('dateOfBirth', $birthdayType, [
                'label' => 'form.label_date_of_birth',
                'required' => false,
                'widget' => 'single_text',
            ])
            ->add('website', $urlType, [
                'label' => 'form.label_website',
                'required' => false,
            ])
            ->add('biography', $textareaType, [
                'label' => 'form.label_biography',
                'required' => false,
            ])
            ->add('locale', $localeType, [
                'label' => 'form.label_locale',
                'required' => false,
            ])
            ->add('timezone', $timezoneType, [
                'label' => 'form.label_timezone',
                'required' => false,
            ])
            ->add('phone', null, [
                'label' => 'form.label_phone',
                'required' => false,
            ])
        ;
    }

    /**
     * {@inheritdoc}
     *
     * NEXT_MAJOR: remove this method.
     *
     * @deprecated Remove it when bumping requirements to Symfony 2.7+
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $this->configureOptions($resolver);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => $this->class,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'sonata_user_profile';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }
}
