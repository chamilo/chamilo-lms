<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Form;

use Chamilo\CoreBundle\Entity\User;
use OTPHP\TOTP;
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
        // Build form fields for password change and 2FA
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
            ->add('enable2FA', CheckboxType::class, [
                'label' => 'Enable two-factor authentication (2FA)',
                'required' => false,
            ])
            ->add('confirm2FACode', TextType::class, [
                'label' => 'Enter your 2FA code',
                'required' => false,
                'mapped' => false,
            ]);

        // Add post-submit validation logic
        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) use ($options) {
            $form = $event->getForm();
            $user = $form->getConfig()->getOption('user');

            if (!$user instanceof User) {
                return;
            }

            $currentPassword = $form->get('currentPassword')->getData();
            $newPassword = $form->get('newPassword')->getData();
            $confirmPassword = $form->get('confirmPassword')->getData();
            $enable2FA = $form->get('enable2FA')->getData();
            $code = $form->get('confirm2FACode')->getData();
            $passwordHasher = $form->getConfig()->getOption('password_hasher');

            // Validate current password and confirmation if user wants to update password
            if (!empty($newPassword)) {
                if (empty($currentPassword)) {
                    $form->get('currentPassword')->addError(new FormError('Current password is required to change your password.'));
                } elseif ($passwordHasher && !$passwordHasher->isPasswordValid($user, $currentPassword)) {
                    $form->get('currentPassword')->addError(new FormError('The current password is incorrect.'));
                }

                // Apply password rules
                foreach (self::validatePassword($newPassword) as $error) {
                    $form->get('newPassword')->addError(new FormError($error));
                }

                // Ensure confirmation matches
                if (!empty($confirmPassword) && $newPassword !== $confirmPassword) {
                    $form->get('confirmPassword')->addError(new FormError('Passwords do not match.'));
                }
            }

            // If 2FA is enabled (or already active), validate the code
            if ($user->getMfaEnabled() || $enable2FA) {
                if (empty($code)) {
                    $form->get('confirm2FACode')->addError(new FormError('The 2FA code is required.'));
                } elseif ($user->getMfaSecret()) {
                    $parts = explode('::', base64_decode($user->getMfaSecret()));
                    if (count($parts) === 2) {
                        [$iv, $encryptedData] = $parts;
                        $decryptedSecret = openssl_decrypt(
                            $encryptedData,
                            'aes-256-cbc',
                            $_ENV['APP_SECRET'],
                            0,
                            $iv
                        );

                        // Validate 2FA code using decrypted TOTP secret
                        $totp = TOTP::create($decryptedSecret);
                        $portal = $options['portal_name'] ?? 'Chamilo';
                        $totp->setLabel($portal . ' - ' . $user->getEmail());

                        if (!$totp->verify($code)) {
                            $form->get('confirm2FACode')->addError(new FormError('The 2FA code is invalid or expired.'));
                        }
                    } else {
                        $form->get('confirm2FACode')->addError(new FormError('Invalid 2FA configuration.'));
                    }
                }
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        // Define custom options required for validation logic
        $resolver->setDefaults([
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'change_password',
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
        $req = \Security::getPasswordRequirements()['min'];

        if (\strlen($password) < $req['length']) {
            $errors[] = 'Password must be at least ' . $req['length'] . ' characters long.';
        }
        if ($req['lowercase'] > 0 && !preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least ' . $req['lowercase'] . ' lowercase characters.';
        }
        if ($req['uppercase'] > 0 && !preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least ' . $req['uppercase'] . ' uppercase characters.';
        }
        if ($req['numeric'] > 0 && !preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least ' . $req['numeric'] . ' numeric characters.';
        }
        if ($req['specials'] > 0 && !preg_match('/[\W]/', $password)) {
            $errors[] = 'Password must contain at least ' . $req['specials'] . ' special characters.';
        }

        return $errors;
    }
}
