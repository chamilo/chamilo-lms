<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Security\Authenticator\Cas;

use Chamilo\CoreBundle\Helpers\AuthenticationConfigHelper;
use Symfony\Component\Ldap\Ldap;
use Symfony\Component\Ldap\Security\LdapUser;
use Symfony\Component\Ldap\Security\LdapUserProvider;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Throwable;

use const FILTER_VALIDATE_EMAIL;

final readonly class CasProfileResolver
{
    public function __construct(
        private AuthenticationConfigHelper $authenticationConfig
    ) {}

    /**
     * @return array{firstname: string, lastname: string, email: string}
     */
    public function fromCas(array $identity): array
    {
        $mapping = $this->authenticationConfig->getCasOptions()['attributes'];
        $profile = [];
        foreach (['firstname' => ['firstname', 'givenName', 'cn'], 'lastname' => ['lastname', 'sn'], 'email' => ['email', 'mail']] as $field => $defaults) {
            $keys = isset($mapping[$field]) && \is_string($mapping[$field]) ? [$mapping[$field]] : $defaults;
            $profile[$field] = '';
            foreach ($keys as $key) {
                $values = array_values(array_unique($identity['attributes'][$key] ?? []));
                if (\count($values) > 1) {
                    throw new CustomUserMessageAuthenticationException('Authentication failed.');
                }
                if (isset($values[0])) {
                    $profile[$field] = trim($values[0]);

                    break;
                }
            }
        }

        return self::validate($profile);
    }

    /**
     * @return array{firstname: string, lastname: string, email: string}
     */
    public function fromLdap(string $username): array
    {
        $config = $this->authenticationConfig->getLdapConfig();
        // CAS proves identity, but LDAP is only an attribute source. Do not bind
        // as the end user, consume passwords or synchronize privileges from LDAP.
        if (!$config['enabled'] || !str_starts_with($config['connection_string'], 'ldaps://')
            || !\in_array((int) ($config['tls_options']['x_tls_require_cert'] ?? 2), [1, 2], true)
            || empty($config['base_dn']) || empty($config['search_dn']) || empty($config['search_password'])
        ) {
            throw new CustomUserMessageAuthenticationException('Authentication failed.');
        }

        try {
            $ldap = Ldap::create('ext_ldap', [
                'connection_string' => $config['connection_string'],
                'options' => array_merge($config['tls_options'], [
                    'protocol_version' => 3,
                    'referrals' => false,
                    'x_tls_require_cert' => 2,
                    'network_timeout' => 5,
                ]),
            ]);
            $provider = new LdapUserProvider(
                $ldap,
                $config['base_dn'],
                $config['search_dn'],
                $config['search_password'],
                ['ROLE_STUDENT'],
                $config['uid_key'],
                '(&(objectClass='.$config['object_class'].')({uid_key}={user_identifier}))',
                null,
                [],
            );
            $user = $provider->loadUserByIdentifier($username);
            if (!$user instanceof LdapUser) {
                throw new CustomUserMessageAuthenticationException('Authentication failed.');
            }
            $entry = $user->getEntry();
            $profile = [];
            foreach (['firstname' => 'givenName', 'lastname' => 'sn', 'email' => 'mail'] as $field => $default) {
                $attribute = $config['data_correspondence'][$field] ?: $default;
                $values = $entry->getAttribute($attribute) ?? [];
                if (1 !== \count(array_unique($values))) {
                    throw new CustomUserMessageAuthenticationException('Authentication failed.');
                }
                $profile[$field] = trim($values[0]);
            }
        } catch (Throwable) {
            throw new CustomUserMessageAuthenticationException('Authentication failed.');
        }

        return self::validate($profile);
    }

    /**
     * @return array{firstname: string, lastname: string, email: string}
     */
    private static function validate(array $profile): array
    {
        foreach ($profile as $value) {
            if ('' === $value || \strlen($value) > 255 || !preg_match('//u', $value) || preg_match('/[\x00-\x1f\x7f]/', $value)) {
                throw new CustomUserMessageAuthenticationException('Authentication failed.');
            }
        }
        if (!filter_var($profile['email'], FILTER_VALIDATE_EMAIL)) {
            throw new CustomUserMessageAuthenticationException('Authentication failed.');
        }

        return $profile;
    }
}
