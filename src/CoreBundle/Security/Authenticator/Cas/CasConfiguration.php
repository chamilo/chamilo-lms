<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Security\Authenticator\Cas;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;

use const FILTER_VALIDATE_IP;

final readonly class CasConfiguration
{
    public function __construct(
        private SettingsManager $settings,
        private AccessUrlHelper $accessUrlHelper,
        #[Autowire('%kernel.environment%')]
        private string $environment = 'prod',
    ) {}

    /**
     * @return array{base: string, validation: string, creation: string, ldap: bool}|null
     */
    public function get(?AccessUrl $url = null): ?array
    {
        $url ??= $this->accessUrlHelper->getCurrent();
        if (null === $url || 1 !== $url->getActive()) {
            return null;
        }

        // A separate settings context preserves the effective main/sub-URL policy
        // without altering the settings context used by the rest of this request.
        $settings = clone $this->settings;
        $settings->setUrl($url);
        if (!\in_array($settings->getSetting('cas.cas_activate', true), [true, 'true'], true)) {
            return null;
        }

        $host = trim((string) $settings->getSetting('cas.cas_server', true));
        $path = trim((string) $settings->getSetting('cas.cas_server_uri', true));
        $port = trim((string) $settings->getSetting('cas.cas_port', true));
        $protocol = strtoupper(trim((string) $settings->getSetting('cas.cas_protocol', true)));
        $creation = strtolower(trim((string) $settings->getSetting('cas.cas_add_user_activate', true)));

        return self::normalize($host, $path, $port, $protocol, $creation, \in_array(
            $settings->getSetting('cas.update_user_info_cas_with_ldap', true),
            [true, 'true'],
            true
        ));
    }

    /**
     * @return array{base: string, validation: string, creation: string, ldap: bool}|null
     */
    public static function normalize(string $host, string $path, string $port, string $protocol, string $creation, bool $ldap): ?array
    {
        // A configured host is not an arbitrary URL. Only HTTPS is supported.
        if (!filter_var($host, FILTER_VALIDATE_IP) && !preg_match('/\A[a-zA-Z0-9](?:[a-zA-Z0-9.-]{0,251}[a-zA-Z0-9])?\z/D', $host)) {
            return null;
        }
        if (str_contains($host, ':')) {
            $host = '['.$host.']';
        }
        $port = '' === $port ? '443' : $port;
        if (!ctype_digit($port) || (int) $port < 1 || (int) $port > 65535) {
            return null;
        }
        if (!preg_match('~\A/?[a-zA-Z0-9_./-]*\z~D', $path) || preg_match('~(?:^|/)\.{1,2}(?:/|$)~', $path) || str_contains($path, '//')) {
            return null;
        }
        $validation = match ($protocol) {
            'CAS1', '1.0' => '/validate',
            '', 'CAS2', '2.0' => '/serviceValidate',
            'CAS3', '3.0' => '/p3/serviceValidate',
            default => null,
        };
        if (null === $validation) {
            return null;
        }

        return [
            'base' => 'https://'.strtolower($host).(443 === (int) $port ? '' : ':'.(int) $port).('' === trim($path, '/') ? '' : '/'.trim($path, '/')),
            'validation' => $validation,
            'creation' => match ($creation) {
                'platform', 'true' => 'platform',
                'extldap' => 'extldap',
                default => 'false',
            },
            'ldap' => $ldap,
        ];
    }

    public function resolvePortal(Request $request): ?AccessUrl
    {
        if (!$request->isSecure() && !\in_array($this->environment, ['dev', 'test'], true)) {
            return null;
        }
        $url = $this->accessUrlHelper->getCurrent();
        // AccessUrlHelper may fall back to the first portal for an unknown host.
        // Never use that fallback for an externally authenticated identity.
        if (null === $url || 1 !== $url->getActive() || !self::matchesPortal($url->getUrl(), $request)) {
            return null;
        }

        return $url;
    }

    public static function matchesPortal(string $url, Request $request): bool
    {
        // CAS service identifiers must use the registered portal, not a client-
        // supplied Host or a return URL. A noncanonical host is rejected.
        return rtrim($url, '/') === rtrim($request->getSchemeAndHttpHost().$request->getBasePath(), '/');
    }
}
