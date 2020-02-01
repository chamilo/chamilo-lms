<?php

namespace SAML2\Certificate;

use SAML2\Configuration\CertificateProvider;
use SAML2\Exception\InvalidArgumentException;

/**
 * @deprecated Please load full certificates instead.
 */
class FingerprintLoader
{
    /**
     * Static method mainly for BC, should be replaced with DI.
     *
     * @param \SAML2\Configuration\CertificateProvider $configuration
     * @return \SAML2\Certificate\FingerprintCollection
     *
     * @deprecated
     */
    public static function loadFromConfiguration(CertificateProvider $configuration)
    {
        $loader = new self();

        return $loader->loadFingerprints($configuration);
    }


    /**
     * Loads the fingerprints from a configurationValue
     *
     * @param \SAML2\Configuration\CertificateProvider $configuration
     * @return \SAML2\Certificate\FingerprintCollection
     *
     * @deprecated
     */
    public function loadFingerprints(CertificateProvider $configuration)
    {
        $fingerprints = $configuration->getCertificateFingerprints();
        if (!is_array($fingerprints) && !$fingerprints instanceof \Traversable) {
            throw InvalidArgumentException::invalidType(
                'array or instanceof \Traversable',
                $fingerprints
            );
        }

        $collection = new FingerprintCollection();
        foreach ($fingerprints as $fingerprint) {
            if (!is_string($fingerprint) && !(is_object($fingerprint) && method_exists($fingerprint, '__toString'))) {
                throw InvalidArgumentException::invalidType(
                    'fingerprint as string or object that can be casted to string',
                    $fingerprint
                );
            }

            $collection->add(new Fingerprint((string) $fingerprint));
        }

        return $collection;
    }
}
