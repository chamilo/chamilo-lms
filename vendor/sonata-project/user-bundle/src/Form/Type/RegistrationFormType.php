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

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class RegistrationFormType extends AbstractType
{
    /**
     * @var array
     */
    protected $mergeOptions;
    /**
     * @var string
     */
    private $class;

    /**
     * @param string $class        The User class name
     * @param array  $mergeOptions Add options to elements
     */
    public function __construct($class, array $mergeOptions = [])
    {
        $this->class = $class;
        $this->mergeOptions = $mergeOptions;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // NEXT_MAJOR: Keep FQCN when bumping Symfony requirement to 2.8+.
        if (method_exists('Symfony\Component\Form\AbstractType', 'getBlockPrefix')) {
            $emailType = 'Symfony\Component\Form\Extension\Core\Type\EmailType';
            $repeatedType = 'Symfony\Component\Form\Extension\Core\Type\RepeatedType';
            $passwordType = 'Symfony\Component\Form\Extension\Core\Type\PasswordType';
        } else {
            $emailType = 'email';
            $repeatedType = 'repeated';
            $passwordType = 'password';
        }

        $builder
            ->add('username', null, array_merge([
                'label' => 'form.username',
                'translation_domain' => 'SonataUserBundle',
            ], $this->mergeOptions))
            ->add('email', $emailType, array_merge([
                'label' => 'form.email',
                'translation_domain' => 'SonataUserBundle',
            ], $this->mergeOptions))
            ->add('plainPassword', $repeatedType, array_merge([
                'type' => $passwordType,
                'options' => ['translation_domain' => 'SonataUserBundle'],
                'first_options' => array_merge([
                    'label' => 'form.password',
                ], $this->mergeOptions),
                'second_options' => array_merge([
                    'label' => 'form.password_confirmation',
                ], $this->mergeOptions),
                'invalid_message' => 'fos_user.password.mismatch',
            ], $this->mergeOptions))
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
            'intention' => 'registration',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'sonata_user_registration';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }
}
