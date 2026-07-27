<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Update;

use Chamilo\CoreBundle\Service\Update\Dto\UpdateManifest;

final readonly class UpdateTrustedKeyring
{
    /**
     * Public release keys trusted by Chamilo for official update packages.
     *
     * The private keys must never be committed. They are kept only on the
     * release-signing infrastructure. Add the official Chamilo 2.x release
     * public key here before enabling signed production updates.
     *
     * @var array<string, array{keyId: string, publicKey: string}>
     */
    private const array OFFICIAL_MINISIGN_PUBLIC_KEYS = [
    ];

    /**
     * Development-only public keys used to simulate the official update server.
     *
     * These keys are trusted only when UpdateConfiguration::ENABLE_DEVELOPMENT_UPDATE_TOOLS
     * is enabled locally.
     *
     * @var array<string, array{keyId: string, publicKey: string}>
     */
    private const array DEVELOPMENT_MINISIGN_PUBLIC_KEYS = [
        'local-test' => [
            'keyId' => 'BD33C3A79B0937B9',
            'publicKey' => 'RWS5Nwmbp8MzvSf1yf9ndm6DHjE9P8HqOJGdxKy9+lKSbsxfy1/Q5YC1',
        ],
    ];

    public function __construct(
        private UpdateConfiguration $updateConfiguration,
    ) {}

    public function getPublicKeyForManifest(UpdateManifest $manifest): ?string
    {
        if ('minisign' !== strtolower((string) $manifest->getSignatureType())) {
            return null;
        }

        $trustedKeys = $this->getTrustedMinisignKeys();
        if ([] === $trustedKeys) {
            return null;
        }

        $manifestKeyId = $manifest->getSignatureKeyId();

        if (null !== $manifestKeyId) {
            foreach ($trustedKeys as $key) {
                if (hash_equals(strtolower($key['keyId']), strtolower($manifestKeyId))) {
                    return $key['publicKey'];
                }
            }

            return null;
        }

        if (1 === \count($trustedKeys)) {
            $firstKey = reset($trustedKeys);

            return \is_array($firstKey) ? $firstKey['publicKey'] : null;
        }

        return null;
    }

    public function hasTrustedPublicKeys(): bool
    {
        return [] !== $this->getTrustedMinisignKeys();
    }

    /**
     * @return string[]
     */
    public function getTrustedKeyIds(): array
    {
        return array_values(array_map(
            static fn (array $key): string => $key['keyId'],
            $this->getTrustedMinisignKeys(),
        ));
    }

    public function getTrustedPublicKeyFingerprint(): ?string
    {
        $trustedKeys = $this->getTrustedMinisignKeys();

        if ([] === $trustedKeys) {
            return null;
        }

        $firstKey = reset($trustedKeys);
        if (!\is_array($firstKey)) {
            return null;
        }

        return 'minisign:'.$firstKey['keyId'].':sha256:'.substr(hash('sha256', $firstKey['publicKey']), 0, 16);
    }

    /**
     * @return array<string, array{keyId: string, publicKey: string}>
     */
    private function getTrustedMinisignKeys(): array
    {
        $trustedKeys = self::OFFICIAL_MINISIGN_PUBLIC_KEYS;

        if ($this->updateConfiguration->allowsDevelopmentUpdateTools()) {
            $trustedKeys = array_merge($trustedKeys, self::DEVELOPMENT_MINISIGN_PUBLIC_KEYS);
        }

        return $trustedKeys;
    }
}
