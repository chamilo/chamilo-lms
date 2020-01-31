<?php

namespace SAML2\Certificate;

use SAML2\Certificate\Exception\InvalidCertificateStructureException;
use SAML2\Certificate\Exception\NoKeysFoundException;
use SAML2\Configuration\CertificateProvider;
use SAML2\Exception\InvalidArgumentException;
use SAML2\Utilities\Certificate;
use SAML2\Utilities\File;

/**
 * KeyLoader
 */
class KeyLoader
{
    /**
     * @var \SAML2\Certificate\KeyCollection
     */
    private $loadedKeys;


    /**
     * Constructor for KeyLoader.
     */
    public function __construct()
    {
        $this->loadedKeys = new KeyCollection();
    }


    /**
     * Extracts the public keys given by the configuration. Mainly exists for BC purposes.
     * Prioritisation order is keys > certData > certificate
     *
     * @param \SAML2\Configuration\CertificateProvider $config
     * @param string|null                              $usage
     * @param bool                                     $required
     * @return \SAML2\Certificate\KeyCollection
     */
    public static function extractPublicKeys(
        CertificateProvider $config,
        $usage = null,
        $required = false
    ) {
        $keyLoader = new self();

        return $keyLoader->loadKeysFromConfiguration($config, $usage, $required);
    }


    /**
     * @param \SAML2\Configuration\CertificateProvider $config
     * @param null|string                             $usage
     * @param bool                                    $required
     * @return \SAML2\Certificate\KeyCollection
     */
    public function loadKeysFromConfiguration(
        CertificateProvider $config,
        $usage = null,
        $required = false
    ) {
        $keys = $config->getKeys();
        $certificateData = $config->getCertificateData();
        $certificateFile = $config->getCertificateFile();

        if ($keys !== null) {
            $this->loadKeys($keys, $usage);
        } elseif ($certificateData !== null) {
            $this->loadCertificateData($certificateData);
        } elseif ($certificateFile !== null) {
            $this->loadCertificateFile($certificateFile);
        }

        if ($required && !$this->hasKeys()) {
            throw new NoKeysFoundException(
                'No keys found in configured metadata, please ensure that either the "keys", "certData" or '
                .'"certificate" entries is available.'
            );
        }

        return $this->getKeys();
    }


    /**
     * Loads the keys given, optionally excluding keys when a usage is given and they
     * are not configured to be used with the usage given
     *
     * @param array $configuredKeys
     * @param string $usage
     * @return void
     */
    public function loadKeys(array $configuredKeys, $usage)
    {
        foreach ($configuredKeys as $keyData) {
            if (isset($keyData['X509Certificate'])) {
                $key = new X509($keyData);
            } else {
                $key = new Key($keyData);
            }

            if ($usage && !$key->canBeUsedFor($usage)) {
                continue;
            }

            $this->loadedKeys->add($key);
        }
    }


    /**
     * Attempts to load a key based on the given certificateData
     *
     * @param string $certificateData
     * @return void
     */
    public function loadCertificateData($certificateData)
    {
        if (!is_string($certificateData)) {
            throw InvalidArgumentException::invalidType('string', $certificateData);
        }

        $this->loadedKeys->add(X509::createFromCertificateData($certificateData));
    }


    /**
     * Loads the certificate in the file given
     *
     * @param string $certificateFile the full path to the cert file.
     * @return void
     */
    public function loadCertificateFile($certificateFile)
    {
        $certificate = File::getFileContents($certificateFile);

        if (!Certificate::hasValidStructure($certificate)) {
            throw new InvalidCertificateStructureException(sprintf(
                'Could not find PEM encoded certificate in "%s"',
                $certificateFile
            ));
        }

        // capture the certificate contents without the delimiters
        preg_match(Certificate::CERTIFICATE_PATTERN, $certificate, $matches);
        $this->loadedKeys->add(X509::createFromCertificateData($matches[1]));
    }


    /**
     * @return \SAML2\Certificate\KeyCollection
     */
    public function getKeys()
    {
        return $this->loadedKeys;
    }


    /**
     * @return bool
     */
    public function hasKeys()
    {
        return !!count($this->loadedKeys);
    }
}
