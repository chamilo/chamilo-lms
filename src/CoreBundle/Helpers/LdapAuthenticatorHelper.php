<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Helpers;

use Chamilo\CoreBundle\Exception\NotAllowedException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Ldap\Entry;
use Symfony\Component\Ldap\Exception\InvalidCredentialsException;
use Symfony\Component\Ldap\Ldap;
use Symfony\Component\Ldap\Security\LdapUser;

use const LDAP_ESCAPE_DN;
use const LDAP_ESCAPE_FILTER;

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
     * Build the base LDAP filter combining objectClass with the optional config filter.
     * Mirrors the logic in LdapSyncUsersCommand so listing and sync always target the same set.
     *
     * Examples with filter = "memberOf=CN=chamilo-users,DC=domain,DC=name":
     *   → (&(objectClass=person)(memberOf=CN=chamilo-users,DC=domain,DC=name))
     *
     * With a compound filter = "|(memberOf=CN=g1,...)(memberOf=CN=g2,...)":
     *   → (&(objectClass=person)(|(memberOf=CN=g1,...)(memberOf=CN=g2,...)))
     */
    private function buildBaseFilter(): string
    {
        $objectClassFilter = "(objectClass={$this->ldapConfig['object_class']})";
        $configFilter = $this->ldapConfig['filter'] ?? '';

        if (!empty($configFilter)) {
            return '(&'.$objectClassFilter.'('.$configFilter.'))';
        }

        return $objectClassFilter;
    }

    private function bindOrFail(): void
    {
        try {
            $this->ldap->bind($this->ldapConfig['search_dn'], $this->ldapConfig['search_password']);
        } catch (InvalidCredentialsException) {
            throw new NotAllowedException();
        }
    }

    /**
     * @return array<int, Entry>
     */
    private function queryAllUsers(): array
    {
        $this->bindOrFail();

        $request = $this->requestStack->getCurrentRequest();
        $dataCorrespondence = $this->ldapConfig['data_correspondence'];

        $keywordFirstname = trim($request->query->get('keyword_firstname', ''));
        $keywordLastname = trim($request->query->get('keyword_lastname', ''));
        $keywordUsername = trim($request->query->get('keyword_username', ''));
        $keywordType = trim($request->query->get('keyword_type', ''));

        $ldapQuery = [$this->buildBaseFilter()];

        if ($keywordUsername) {
            $ldapQuery[] = '(uid='.ldap_escape($keywordUsername, '', LDAP_ESCAPE_FILTER).')';
        }

        if ($keywordLastname) {
            $ldapQuery[] = '('.$dataCorrespondence['lastname'].'='.ldap_escape($keywordLastname, '', LDAP_ESCAPE_FILTER).'*)';
        }

        if ($keywordFirstname) {
            $ldapQuery[] = '('.$dataCorrespondence['firstname'].'='.ldap_escape($keywordFirstname, '', LDAP_ESCAPE_FILTER).'*)';
        }

        if ($keywordType && 'all' !== $keywordType) {
            $ldapQuery[] = '(employeeType='.ldap_escape($keywordType, '', LDAP_ESCAPE_FILTER).')';
        }

        $query = \count($ldapQuery) > 1 ? '(&'.implode('', $ldapQuery).')' : $ldapQuery[0];

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
        $this->bindOrFail();

        return $this->ldap
            ->query(
                'ou='.ldap_escape($ou, '', LDAP_ESCAPE_DN).','.$this->ldapConfig['base_dn'],
                $this->buildBaseFilter()
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

            $uid = $this->getAttr($ldapUser, $userIdentifier);
            $user[] = $uid;
            $user[] = $uid;

            if ($isWesternNameOrder) {
                $user[] = $this->getAttr($ldapUser, $dataCorrespondence['firstname'] ?? null);
                $user[] = $this->getAttr($ldapUser, $dataCorrespondence['lastname'] ?? null);
            } else {
                $user[] = $this->getAttr($ldapUser, $dataCorrespondence['lastname'] ?? null);
                $user[] = $this->getAttr($ldapUser, $dataCorrespondence['firstname'] ?? null);
            }

            $user[] = $this->getAttr($ldapUser, $dataCorrespondence['email'] ?? null);
            $user[] = $uid;

            $users[] = $user;
        }

        return $users;
    }

    /**
     * Find a single LDAP user by their identifier (uid) and return a LdapUser ready for createUser().
     * Uses the same explicit bind + search pattern as queryAllUsers() so it works reliably.
     * The config filter (objectClass + optional filter) is applied so only allowed users can be imported.
     */
    public function findLdapUserByIdentifier(string $identifier): ?LdapUser
    {
        $this->bindOrFail();

        $uidKey = $this->ldapConfig['uid_key'];
        $escaped = ldap_escape($identifier, '', LDAP_ESCAPE_FILTER);
        $uidFilter = '('.$uidKey.'='.$escaped.')';
        $filter = '(&'.$this->buildBaseFilter().$uidFilter.')';

        $entries = $this->ldap
            ->query($this->ldapConfig['base_dn'], $filter)
            ->execute()
            ->toArray()
        ;

        if (0 === \count($entries)) {
            return null;
        }

        $entry = $entries[0];
        $dataCorrespondence = array_filter($this->ldapConfig['data_correspondence']);

        $extraFields = [];
        foreach ($dataCorrespondence as $ldapAttr) {
            $values = $entry->getAttribute((string) $ldapAttr);
            if (null !== $values) {
                $extraFields[(string) $ldapAttr] = $values;
            }
        }

        return new LdapUser($entry, $identifier, null, ['ROLE_STUDENT'], $extraFields);
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
                'username' => $this->getAttr($ldapUser, $userIdentifier),
                'firstname' => $this->getAttr($ldapUser, $dataCorrespondence['firstname'] ?? null),
                'lastname' => $this->getAttr($ldapUser, $dataCorrespondence['lastname'] ?? null),
                'email' => $this->getAttr($ldapUser, $dataCorrespondence['email'] ?? null),
                'password' => $this->getAttr($ldapUser, $passwordAttribute),
            ];
        }

        return $users;
    }

    /**
     * Safely reads the first value of an LDAP entry attribute.
     * Returns an empty string when the attribute name is null/empty or the entry doesn't carry it.
     */
    private function getAttr(Entry $entry, ?string $attrName): string
    {
        if (null === $attrName || '' === $attrName) {
            return '';
        }

        return ($entry->getAttribute($attrName) ?? [''])[0];
    }
}
