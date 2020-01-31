<?php

namespace SAML2\Configuration;

use \SimpleSAML\Configuration;

/**
 * Backwards compatibility helper for SimpleSAMLphp
 */
class SimpleSAMLConverter
{
    /**
     * @param \SimpleSAML\Configuration $configuration
     * @param string                    $certificatePrefix
     *
     * @return \SAML2\Configuration\IdentityProvider
     */
    public static function convertToIdentityProvider(
        Configuration $configuration,
        $certificatePrefix = ''
    ) {
        $pluckedConfiguration = static::pluckConfiguration($configuration, $certificatePrefix);
        static::enrichForDecryptionProvider($configuration, $pluckedConfiguration);
        static::enrichForIdentityProvider($configuration, $pluckedConfiguration);

        return new IdentityProvider($pluckedConfiguration);
    }


    /**
     * @param \SimpleSAML\Configuration $configuration
     * @param string                    $certificatePrefix
     *
     * @return \SAML2\Configuration\ServiceProvider
     */
    public static function convertToServiceProvider(
        Configuration $configuration,
        $certificatePrefix = ''
    ) {
        $pluckedConfiguration = static::pluckConfiguration($configuration, $certificatePrefix);
        static::enrichForServiceProvider($configuration, $pluckedConfiguration);
        static::enrichForDecryptionProvider($configuration, $pluckedConfiguration);

        return new ServiceProvider($pluckedConfiguration);
    }


    /**
     * @param \SimpleSAML\Configuration $configuration
     * @param string                    $prefix
     *
     * @return array
     */
    protected static function pluckConfiguration(Configuration $configuration, $prefix = '')
    {
        $extracted = [];

        // ported from
        // https://github.com/simplesamlphp/simplesamlphp/blob/3d735912342767d391297cc5e13272a76730aca0/lib/SimpleSAML/Configuration.php#L1092
        if ($configuration->hasValue($prefix.'keys')) {
            $extracted['keys'] = $configuration->getArray($prefix.'keys');
        }

        // ported from
        // https://github.com/simplesamlphp/simplesamlphp/blob/3d735912342767d391297cc5e13272a76730aca0/lib/SimpleSAML/Configuration.php#L1108
        if ($configuration->hasValue($prefix.'certData')) {
            $extracted['certificateData'] = $configuration->getString($prefix.'certData');
        }

        // ported from
        // https://github.com/simplesamlphp/simplesamlphp/blob/3d735912342767d391297cc5e13272a76730aca0/lib/SimpleSAML/Configuration.php#L1119
        if ($configuration->hasValue($prefix.'certificate')) {
            $extracted['certificateData'] = $configuration->getString($prefix.'certificate');
        }

        // ported from
        // https://github.com/simplesamlphp/simplesamlphp/blob/3d735912342767d391297cc5e13272a76730aca0/modules/saml/lib/Message.php#L161
        if ($configuration->hasValue($prefix.'certFingerprint')) {
            $extracted['certificateFingerprint'] = $configuration->getArrayizeString('certFingerprint');
        }

        $extracted['assertionEncryptionEnabled'] = $configuration->getBoolean('assertion.encryption', false);

        if ($configuration->has('sharedKey')) {
            $extracted['sharedKey'] = $configuration->getString('sharedKey');
        }

        return $extracted;
    }


    /**
     * @param \SimpleSAML\Configuration $configuration
     * @param array                     $baseConfiguration
     *
     * @return void
     */
    protected static function enrichForIdentityProvider(Configuration $configuration, array &$baseConfiguration)
    {
        $baseConfiguration['base64EncodedAttributes'] = $configuration->getBoolean('base64attributes', false);
        $baseConfiguration['entityId'] = $configuration->getString('entityid');
    }


    /**
     * @param \SimpleSAML\Configuration $configuration
     * @param array                     $baseConfiguration
     *
     * @return void
     */
    protected static function enrichForServiceProvider(Configuration $configuration, array &$baseConfiguration)
    {
        $baseConfiguration['entityId'] = $configuration->getString('entityid');
    }


    /**
     * @param \SimpleSAML\Configuration $configuration
     * @param array                     $baseConfiguration
     *
     * @return void
     */
    protected static function enrichForDecryptionProvider(
        Configuration $configuration,
        array &$baseConfiguration
    ) {
        if ($configuration->has('sharedKey')) {
            $baseConfiguration['sharedKey'] = $configuration->getString('sharedKey', null);
        }

        if ($configuration->has('new_privatekey')) {
            $baseConfiguration['privateKeys'][] = new PrivateKey(
                $configuration->getString('new_privatekey'),
                PrivateKey::NAME_NEW,
                $configuration->getString('new_privatekey_pass', null)
            );
        }

        if ($configuration->getBoolean('assertion.encryption', false)) {
            $baseConfiguration['privateKeys'][] = new PrivateKey(
                $configuration->getString('privatekey'),
                PrivateKey::NAME_DEFAULT,
                $configuration->getString('privatekey_pass', null)
            );

            if ($configuration->has('encryption.blacklisted-algorithms')) {
                $baseConfiguration['blacklistedEncryptionAlgorithms'] = $configuration
                    ->get('encryption.blacklisted-algorithms');
            }
        }
    }
}
