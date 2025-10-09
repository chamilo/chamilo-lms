<?php

namespace Chamilo\CoreBundle\Service;

use Chamilo\CoreBundle\Entity\User;
use Symfony\Polyfill\Intl\Normalizer\Normalizer;

class StandardizationService
{
    public static function sort(array $array): array
    {
        usort($array, function ($a, $b) {
            return strcmp(self::standardizeString($a), self::standardizeString($b));
        });
        return $array;
    }

    /**
     * @param array $users of User entity
     * @param bool $addEmail if true, add an email field
     * @param string|null $countryCode if null take the current countryCode
     * @return array with an identity field containing first and last name, if $addEmail = true : add an email field
     */
    public static function sortByNameByCountryAndStandardizeName(array $users, bool $addEmail = false, string $countryCode = null): array
    {
        if ($countryCode === null) {
            $countryCode = api_get_language_isocode();
        }

        if (_api_get_person_name_convention($countryCode, 'sort_by')) {
            usort($users, function ($a, $b) {
                return strcmp(self::standardizeString($a->getLastName()), self::standardizeString($b->getLastName()));
            });
        } else {
            usort($users, function ($a, $b) {
                return strcmp(self::standardizeString($a->getFirstName()), self::standardizeString($b->getFirstName()));
            });
        }

        $standardizedNames = [];
        if ($addEmail) {
            foreach ($users as $user) {
                $standardizedNames[] = [
                    'identity' => self::standardizeName($user, $countryCode),
                    'email' => $user->getEmail()
                ];
            }
        } else {
            foreach ($users as $user) {
                $standardizedNames[] = ['identity' => self::standardizeName($user, $countryCode)];
            }
        }
        return $standardizedNames;
    }

    public static function standardizeName(User $user, string $countryCode = null): string
    {
        if ($countryCode === null) {
            $countryCode = api_get_language_isocode();
        }

        return ucwords(api_get_person_name($user->getFirstName(), $user->getLastName(), null, null, $countryCode));
    }

    public static function standardizeString(string $string): string
    {
        $string = trim($string, " \t\n\r\0\x0B()");
        $string = preg_replace('/[\x{0300}-\x{036f}]/u', '', normalizer_normalize($string, Normalizer::FORM_D));
        return mb_strtolower($string, 'UTF-8');
    }

}
