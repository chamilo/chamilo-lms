<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Helpers;

use Chamilo\CoreBundle\Exception\NotAllowedException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Ldap\Entry;
use Symfony\Component\Ldap\Exception\InvalidCredentialsException;
use Symfony\Component\Ldap\Ldap;

readonly class LdapAuthenticatorHelper
{
    protected array $ldapConfig;

    public function __construct(
        private RequestStack $requestStack,
        private Ldap $ldap,
        AuthenticationConfigHelper $authConfigHelper,
    ) {
        $this->ldapConfig = $authConfigHelper->getLdapConfig();
    }

    /**
     * @return array<int, Entry>
     */
    private function queryAllUsers(): array
    {
        try {
            $this->ldap->bind($this->ldapConfig['search_dn'], $this->ldapConfig['search_password']);
        } catch (InvalidCredentialsException) {
            throw new NotAllowedException();
        }

        $request = $this->requestStack->getCurrentRequest();
        $dataCorrespondence = $this->ldapConfig['data_correspondence'];

        $keywordFirstname = trim($request->query->get('keyword_firstname', ''));
        $keywordLastname = trim($request->query->get('keyword_lastname', ''));
        $keywordUsername = trim($request->query->get('keyword_username', ''));
        $keywordType = trim($request->query->get('keyword_type', ''));

        $ldapQuery = [
            "(objectClass={$this->ldapConfig['object_class']})",
        ];

        if ($keywordUsername) {
            $ldapQuery[] = "(uid=$keywordUsername)";
        }

        if ($keywordLastname) {
            $ldapQuery[] = "({$dataCorrespondence['lastname']}=$keywordLastname*)";
        }

        if ($keywordFirstname) {
            $ldapQuery[] = "({$dataCorrespondence['firstname']}=$keywordFirstname*)";
        }

        if ($keywordType && 'all' !== $keywordType) {
            $ldapQuery[] = "(employeeType=$keywordType)";
        }

        $query = \count($ldapQuery) > 1 ? '(& '.implode(' ', $ldapQuery).' )' : $ldapQuery[0];

        return $this->ldap
            ->query($this->ldapConfig['base_dn'], $query)
            ->execute()
            ->toArray()
        ;
    }

    /**
     * @return array<int, Entry>
     */
    private function queryByOu(string $ou): array
    {
        try {
            $this->ldap->bind($this->ldapConfig['search_dn'], $this->ldapConfig['search_password']);
        } catch (InvalidCredentialsException) {
            throw new NotAllowedException();
        }

        return $this->ldap
            ->query(
                "ou=$ou,".$this->ldapConfig['base_dn'],
                "(objectClass={$this->ldapConfig['object_class']})"
            )
            ->execute()
            ->toArray()
        ;
    }

    public function countUsers(array $params): int
    {
        return \count($this->queryAllUsers());
    }

    public function getAllUsers(int $from, int $numberOfItems, int $column, string $direction, array $params): array
    {
        $isWesternNameOrder = api_is_western_name_order();
        $ldapUsers = $this->queryAllUsers();
        $userIdentifier = $this->ldapConfig['uid_key'];
        $dataCorrespondence = $this->ldapConfig['data_correspondence'];

        $users = [];

        foreach ($ldapUsers as $ldapUser) {
            $user = [];

            $user[] = $ldapUser->getAttribute($userIdentifier)[0];
            $user[] = $ldapUser->getAttribute($userIdentifier)[0];

            if ($isWesternNameOrder) {
                $user[] = $ldapUser->getAttribute($dataCorrespondence['firstname'])[0];
                $user[] = $ldapUser->getAttribute($dataCorrespondence['lastname'])[0];
            } else {
                $user[] = $ldapUser->getAttribute($dataCorrespondence['lastname'])[0];
                $user[] = $ldapUser->getAttribute($dataCorrespondence['firstname'])[0];
            }

            $user[] = $ldapUser->getAttribute($dataCorrespondence['email'])[0];
            $user[] = $ldapUser->getAttribute($userIdentifier)[0];

            $users[] = $user;
        }

        return $users;
    }

    public function getUsersByOu(string $ou): array
    {
        $ldapUsers = $this->queryByOu($ou);
        $userIdentifier = $this->ldapConfig['uid_key'];
        $passwordAttribute = $this->ldapConfig['password_attribute'];
        $dataCorrespondence = $this->ldapConfig['data_correspondence'];

        $users = [];

        foreach ($ldapUsers as $ldapUser) {
            $users[] = [
                'username' => $ldapUser->getAttribute($userIdentifier)[0],
                'firstname' => $ldapUser->getAttribute($dataCorrespondence['firstname'])[0],
                'lastname' => $ldapUser->getAttribute($dataCorrespondence['lastname'])[0],
                'email' => $ldapUser->getAttribute($dataCorrespondence['email'])[0],
                'password' => $ldapUser->getAttribute($passwordAttribute)[0],
            ];
        }

        return $users;
    }
}
