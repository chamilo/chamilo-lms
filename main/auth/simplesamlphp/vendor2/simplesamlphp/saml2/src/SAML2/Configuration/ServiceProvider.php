<?php

namespace SAML2\Configuration;

use RobRichards\XMLSecLibs\XMLSecurityKey;

/**
 * Basic Configuration Wrapper
 */
class ServiceProvider extends ArrayAdapter implements
    CertificateProvider,
    DecryptionProvider,
    EntityIdProvider
{
    /**
     * @return mixed
     */
    public function getKeys()
    {
        return $this->get('keys');
    }


    /**
     * @return mixed
     */
    public function getCertificateData()
    {
        return $this->get('certificateData');
    }


    /**
     * @return mixed
     */
    public function getCertificateFile()
    {
        return $this->get('certificateFile');
    }


    /**
     * @return mixed
     * @deprecated Please use getCertificateData() or getCertificateFile().
     */
    public function getCertificateFingerprints()
    {
        return $this->get('certificateFingerprints');
    }


    /**
     * @return mixed
     */
    public function getEntityId()
    {
        return $this->get('entityId');
    }


    /**
     * @return mixed
     */
    public function isAssertionEncryptionRequired()
    {
        return $this->get('assertionEncryptionEnabled');
    }


    /**
     * @return mixed
     */
    public function getSharedKey()
    {
        return $this->get('sharedKey');
    }


    /**
     * @param string $name
     * @param bool $required
     * @return mixed|null
     */
    public function getPrivateKey($name, $required = false)
    {
        $privateKeys = $this->get('privateKeys');
        $key         = array_filter($privateKeys, function (PrivateKey $key) use ($name) {
            return $key->getName() === $name;
        });

        $keyCount = count($key);
        if ($keyCount !== 1 && $required) {
            throw new \RuntimeException(sprintf(
                'Attempted to get privateKey by name "%s", found "%d" keys, where only one was expected. Please '
                . 'verify that your configuration is correct',
                $name,
                $keyCount
            ));
        }

        if (!$keyCount) {
            return null;
        }

        return array_pop($key);
    }


    /**
     * @return mixed
     */
    public function getBlacklistedAlgorithms()
    {
        return $this->get('blacklistedEncryptionAlgorithms', [XMLSecurityKey::RSA_1_5]);
    }
}
