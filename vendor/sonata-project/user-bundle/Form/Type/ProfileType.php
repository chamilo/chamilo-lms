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
        $builder
            ->add('gender', 'sonata_user_gender', array(
                'label' => 'form.label_gender',
                'required' => true,
                'translation_domain' => 'SonataUserBundle',
                'choices' => array(
                    UserInterface::GENDER_FEMALE => 'gender_female',
                    UserInterface::GENDER_MALE => 'gender_male',
                ),
            ))
            ->add('firstname', null, array(
                'label' => 'form.label_firstname',
                'required' => false,
            ))
            ->add('lastname', null, array(
                'label' => 'form.label_lastname',
                'required' => false,
            ))
            ->add('dateOfBirth', 'birthday', array(
                'label' => 'form.label_date_of_birth',
                'required' => false,
                'widget' => 'single_text',
            ))
            ->add('website', 'url', array(
                'label' => 'form.label_website',
                'required' => false,
            ))
            ->add('biography', 'textarea', array(
                'label' => 'form.label_biography',
                'required' => false,
            ))
            ->add('locale', 'locale', array(
                'label' => 'form.label_locale',
                'required' => false,
            ))
            ->add('timezone', 'timezone', array(
                'label' => 'form.label_timezone',
                'required' => false,
            ))
            ->add('phone', null, array(
                'label' => 'form.label_phone',
                'required' => false,
            ))
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
        $resolver->setDefaults(array(
            'data_class' => $this->class,
        ));
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
