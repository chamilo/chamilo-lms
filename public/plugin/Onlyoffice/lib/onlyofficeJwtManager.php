<?php

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Onlyoffice\DocsIntegrationSdk\Manager\Security\JwtManager;

class OnlyofficeJwtManager extends JwtManager
{
    private OnlyofficeAppsettings $appSettings;

    public function __construct(OnlyofficeAppsettings $settingsManager)
    {
        parent::__construct($settingsManager);
        $this->appSettings = $settingsManager;
    }

    public function encode($payload, $key, $algorithm = 'HS256')
    {
        return JWT::encode($payload, $key, $algorithm);
    }

    public function decode($token, $key, $algorithm = 'HS256')
    {
        return JWT::decode($token, new Key($key, $algorithm));
    }

    public function getHash($object)
    {
        return $this->encode($object, $this->getSigningKey());
    }

    public function getSigningKey(): string
    {
        $platformKey = $this->getPlatformSecurityKey();
        if ('' !== $platformKey) {
            return $platformKey;
        }

        $pluginKey = $this->getPluginJwtSecret();
        if ('' !== $pluginKey) {
            return $pluginKey;
        }

        throw new \RuntimeException('OnlyOffice signing key is not configured.');
    }

    private function getPlatformSecurityKey(): string
    {
        global $_configuration;

        if (isset($_configuration['security_key']) && is_string($_configuration['security_key'])) {
            $value = trim($_configuration['security_key']);
            if ('' !== $value) {
                return $value;
            }
        }

        return '';
    }

    private function getPluginJwtSecret(): string
    {
        $candidates = [
            $this->appSettings->getSetting('jwt_secret'),
            $this->appSettings->getSetting('onlyoffice_jwt_secret'),
            $this->appSettings->getSetting('secret'),
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate)) {
                $candidate = trim($candidate);
                if ('' !== $candidate) {
                    return $candidate;
                }
            }
        }

        return '';
    }
}
