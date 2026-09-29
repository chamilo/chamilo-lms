<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Security;

use Security;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Validates a plain password against the platform's configured strength policy
 * (Security::getPasswordRequirements()). Shared by ChangePasswordType (self-service form)
 * and any admin-driven password write (e.g. UpdateUserPasswordAction) so the same rules
 * apply everywhere a password is set, not just in the web form.
 */
final readonly class PasswordPolicyValidator
{
    public function __construct(
        private TranslatorInterface $translator
    ) {}

    /**
     * @return string[] translated violation messages; empty when the password satisfies the policy
     */
    public function validate(string $password): array
    {
        $errors = [];
        $req = Security::getPasswordRequirements()['min'];

        $len = \strlen($password);
        $lower = preg_match_all('/[a-z]/', $password);
        $upper = preg_match_all('/[A-Z]/', $password);
        $digits = preg_match_all('/\d/', $password);
        $specials = preg_match_all('/[^a-zA-Z0-9]/', $password);

        if ($len < $req['length']) {
            $errors[] = $this->translator->trans('Password must be at least %length% characters long.', ['%length%' => $req['length']]);
        }
        if ($req['lowercase'] > 0 && $lower < $req['lowercase']) {
            $errors[] = $this->translator->trans('Password must contain at least %count% lowercase characters.', ['%count%' => $req['lowercase']]);
        }
        if ($req['uppercase'] > 0 && $upper < $req['uppercase']) {
            $errors[] = $this->translator->trans('Password must contain at least %count% uppercase characters.', ['%count%' => $req['uppercase']]);
        }
        if ($req['numeric'] > 0 && $digits < $req['numeric']) {
            $errors[] = $this->translator->trans('Password must contain at least %count% numerical (0-9) characters.', ['%count%' => $req['numeric']]);
        }
        if ($req['specials'] > 0 && $specials < $req['specials']) {
            $errors[] = $this->translator->trans('Password must contain at least %count% special characters.', ['%count%' => $req['specials']]);
        }

        return $errors;
    }
}
