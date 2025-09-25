<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Form;

use Chamilo\CoreBundle\Entity\User;
use OTPHP\TOTP;
use Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
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
        // Add basic fields for password change
        $builder
            ->add('currentPassword', PasswordType::class, [
                'label' => 'Current password',
                'required' => false,
                'mapped' => false,
            ])
            ->add('newPassword', PasswordType::class, [
                'label' => 'New password',
                'required' => false,
                'mapped' => false,
            ])
            ->add('confirmPassword', PasswordType::class, [
                'label' => 'Confirm new password',
                'required' => false,
                'mapped' => false,
            ])
        ;

        // Show 2FA fields only when allowed by controller/options
        if ($options['enable_2fa_field']) {
            $builder->add('enable2FA', CheckboxType::class, [
                'label' => 'Enable two-factor authentication (2FA)',
                'required' => false,
            ]);

            $builder->add('confirm2FACode', TextType::class, [
                'label' => 'Enter your 2FA code',
                'required' => false,
                'mapped' => false,
            ]);
        }

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) use ($options): void {
            $form = $event->getForm();
            $user = $form->getConfig()->getOption('user');

            if (!$user instanceof User) {
                return;
            }

            $currentPassword = $form->get('currentPassword')->getData();
            $newPassword = $form->get('newPassword')->getData();
            $confirmPassword = $form->get('confirmPassword')->getData();
            $enable2FA = $form->has('enable2FA')
                ? (bool) $form->get('enable2FA')->getData()
                : false;
            $code = $form->has('confirm2FACode')
                ? $form->get('confirm2FACode')->getData()
                : null;
            $passwordHasher = $form->getConfig()->getOption('password_hasher');

            // Validate current password and confirmation if user wants to update password
            if (!empty($newPassword)) {
                if (empty($currentPassword)) {
                    $form->get('currentPassword')->addError(new FormError('Current password is required to change your password.'));
                } elseif ($passwordHasher && !$passwordHasher->isPasswordValid($user, $currentPassword)) {
                    $form->get('currentPassword')->addError(new FormError('The current password is incorrect.'));
                }

                foreach (self::validatePassword($newPassword) as $error) {
                    $form->get('newPassword')->addError(new FormError($error));
                }

                if (!empty($confirmPassword) && $newPassword !== $confirmPassword) {
                    $form->get('confirmPassword')->addError(new FormError('Passwords do not match.'));
                }
            }

            // Guard 2FA validation behind the global toggle AND presence of the field
            if (true === $options['global_2fa_enabled'] && $form->has('confirm2FACode')) {
                if ($user->getMfaEnabled() || $enable2FA) {
                    if (empty($code)) {
                        $form->get('confirm2FACode')->addError(new FormError('The 2FA code is required.'));
                    } elseif ($user->getMfaSecret()) {
                        $parts = explode('::', base64_decode($user->getMfaSecret()));
                        if (2 === \count($parts)) {
                            [$iv, $encryptedData] = $parts;
                            $decryptedSecret = openssl_decrypt(
                                $encryptedData,
                                'aes-256-cbc',
                                $_ENV['APP_SECRET'],
                                0,
                                $iv
                            );

                            $totp = TOTP::create($decryptedSecret);
                            $portal = $options['portal_name'] ?? 'Chamilo';
                            $totp->setLabel($portal.' - '.$user->getEmail());

                            if (!$totp->verify($code)) {
                                $form->get('confirm2FACode')->addError(new FormError('The 2FA code is invalid or expired.'));
                            }
                        } else {
                            $form->get('confirm2FACode')->addError(new FormError('Invalid 2FA configuration.'));
                        }
                    }
                }
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'change_password',
            'enable_2fa_field' => true,
            'global_2fa_enabled' => true,
            'user' => null,
            'portal_name' => 'Chamilo',
            'password_hasher' => null,
        ]);
    }

    /**
     * Validate password against security rules defined in settings.
     */
    private static function validatePassword(string $password): array
    {
        $errors = [];
        $req = Security::getPasswordRequirements()['min'];

        $len = \strlen($password);
        $lower = preg_match_all('/[a-z]/', $password);
        $upper = preg_match_all('/[A-Z]/', $password);
        $digits = preg_match_all('/\d/', $password);
        $specials = preg_match_all('/[^a-zA-Z0-9]/', $password);

        if ($len < $req['length']) {
            $errors[] = 'Password must be at least '.$req['length'].' characters long.';
        }
        if ($req['lowercase'] > 0 && $lower < $req['lowercase']) {
            $errors[] = 'Password must contain at least '.$req['lowercase'].' lowercase character(s).';
        }
        if ($req['uppercase'] > 0 && $upper < $req['uppercase']) {
            $errors[] = 'Password must contain at least '.$req['uppercase'].' uppercase character(s).';
        }
        if ($req['numeric'] > 0 && $digits < $req['numeric']) {
            $errors[] = 'Password must contain at least '.$req['numeric'].' numeric character(s).';
        }
        if ($req['specials'] > 0 && $specials < $req['specials']) {
            $errors[] = 'Password must contain at least '.$req['specials'].' special character(s).';
        }

        return $errors;
    }
}
