<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Security\Authenticator\Ldap;

use Chamilo\CoreBundle\Helpers\AuthenticationConfigHelper;
use Symfony\Component\Ldap\Adapter\AdapterInterface;
use Symfony\Component\Ldap\Adapter\ConnectionInterface;
use Symfony\Component\Ldap\Adapter\EntryManagerInterface;
use Symfony\Component\Ldap\Adapter\ExtLdap\Adapter;
use Symfony\Component\Ldap\Adapter\QueryInterface;

class ExtAdapter implements AdapterInterface
{
    private ?Adapter $adapter = null;

    public function __construct(
        private readonly AuthenticationConfigHelper $authConfigHelper,
    ) {}

    public function getConnection(): ConnectionInterface
    {
        return $this->getAdapter()->getConnection();
    }

    public function createQuery(string $dn, string $query, array $options = []): QueryInterface
    {
        return $this->getAdapter()->createQuery($dn, $query, $options);
    }

    public function getEntryManager(): EntryManagerInterface
    {
        return $this->getAdapter()->getEntryManager();
    }

    public function escape(string $subject, string $ignore = '', int $flags = 0): string
    {
        return $this->getAdapter()->escape($subject, $ignore, $flags);
    }

    public function reset(): void
    {
        // Symfony resets LDAP even when no LDAP operation was performed.
        $this->adapter?->reset();
        $this->adapter = null;
    }

    private function getAdapter(): Adapter
    {
        if (null !== $this->adapter) {
            return $this->adapter;
        }

        $params = $this->authConfigHelper->getLdapConfig();

        return $this->adapter = new Adapter([
            'connection_string' => $params['connection_string'],
            'options' => array_merge(
                [
                    'protocol_version' => $params['protocol_version'],
                    'referrals' => $params['referrals'],
                ],
                $params['tls_options'],
            ),
        ]);
    }
}
