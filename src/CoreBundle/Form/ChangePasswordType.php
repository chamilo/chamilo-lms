<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @template T of object
 *
 * @extends AbstractType<T>
 */
class ChangePasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('currentPassword', PasswordType::class, [
                'label' => 'Current password',
                'required' => false,
            ])
            ->add('newPassword', PasswordType::class, [
                'label' => 'New password',
                'required' => false,
            ])
            ->add('confirmPassword', PasswordType::class, [
                'label' => 'Confirm new password',
                'required' => false,
            ])
        ;

        if ($options['enable_2fa_field']) {
            $builder->add('enable2FA', CheckboxType::class, [
                'label' => 'Enable two-factor authentication (2FA)',
                'required' => false,
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'change_password',
            'enable_2fa_field' => true,
        ]);
    }
}
