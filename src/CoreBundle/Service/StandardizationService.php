<?php

namespace Chamilo\CoreBundle\Service;

use Chamilo\CoreBundle\Entity\User;
use Symfony\Polyfill\Intl\Normalizer\Normalizer;

class StandardizationService
{
    /**
     * Sorts an array of strings alphabetically after standardizing them.
     *
     * @param array $strings Array of strings to sort
     * @return array Sorted array of strings
     */
    public static function sort(array $strings): array
    {
        usort($strings, function ($a, $b) {
            return strcmp(self::standardizeString($a), self::standardizeString($b));
        });
        return $strings;
    }

    /**
     * Sorts an array of User entities by name (first or last name based on country convention)
     * and returns an array of standardized names, optionally including email.
     *
     * @param array $users Array of User entities
     * @param bool $addEmail If true, includes the email field in the result
     * @param string|null $languageIsoCode ISO language code; if null, uses the current language code
     * @return array Array of associative arrays with 'identity' field (standardized name)
     *               and optional 'email' field if $addEmail is true
     */
    public static function sortByNameByCountryAndStandardizeName(array $users, bool $addEmail = false, string $languageIsoCode = null): array
    {
        if ($languageIsoCode === null) {
            $languageIsoCode = api_get_language_isocode();
        }

        if (_api_get_person_name_convention($languageIsoCode, 'sort_by')) {
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
                    'identity' => self::standardizeName($user, $languageIsoCode),
                    'email' => $user->getEmail()
                ];
            }
        } else {
            foreach ($users as $user) {
                $standardizedNames[] = ['identity' => self::standardizeName($user, $languageIsoCode)];
            }
        }
        return $standardizedNames;
    }

    /**
     * Standardizes a user's full name based on country-specific conventions.
     *
     * @param User $user The User entity containing first and last names
     * @param string|null $languageIsoCode ISO language code; if null, uses the current language code
     * @return string Standardized full name with capitalized words
     */
    public static function standardizeName(User $user, string $languageIsoCode = null): string
    {
        if ($languageIsoCode === null) {
            $languageIsoCode = api_get_language_isocode();
        }

        return ucwords(api_get_person_name($user->getFirstName(), $user->getLastName(), null, null, $languageIsoCode));
    }

    /**
     * Standardizes a string by trimming whitespace, removing diacritics, and converting to lowercase to use sort functions.
     *
     * @param string $string The input string to standardize
     * @return string The standardized string in lowercase without diacritics
     */
    public static function standardizeString(string $string): string
    {
        $string = trim($string, " \t\n\r\0\x0B()");
        $string = preg_replace('/[\x{0300}-\x{036f}]/u', '', normalizer_normalize($string, Normalizer::FORM_D));
        return mb_strtolower($string, 'UTF-8');
    }
}
