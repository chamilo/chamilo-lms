<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Update;

use Chamilo\CoreBundle\Service\Update\Dto\UpdateAvailabilityResult;
use Chamilo\CoreBundle\Service\Update\Dto\UpdateManifest;

final readonly class UpdateAvailabilityChecker
{
    public function __construct(
        private InstalledChamiloVersionProvider $installedVersionProvider,
    ) {}

    public function check(UpdateManifest $manifest, ?string $installedVersion = null): UpdateAvailabilityResult
    {
        $installedVersion = null !== $installedVersion && '' !== trim($installedVersion)
            ? trim($installedVersion)
            : $this->installedVersionProvider->getInstalledVersion();
        $targetVersion = $this->installedVersionProvider->normalizeVersion($manifest->getVersion()) ?? $manifest->getVersion();

        if ('unknown' === $installedVersion) {
            return new UpdateAvailabilityResult(
                $installedVersion,
                $targetVersion,
                false,
                true,
                false,
                false,
                'unknown_installed_version',
                'Installed Chamilo version could not be detected automatically. Continue only after confirming this update is compatible.',
                'verify',
            );
        }

        if (
            !$this->installedVersionProvider->isComparableVersion($installedVersion)
            || !$this->installedVersionProvider->isComparableVersion($targetVersion)
        ) {
            return new UpdateAvailabilityResult(
                $installedVersion,
                $targetVersion,
                false,
                true,
                false,
                false,
                'not_comparable',
                'Installed or target version is not in a comparable semantic version format. Continue only after manual verification.',
                'verify',
            );
        }

        $compare = version_compare($targetVersion, $installedVersion);

        if (0 < $compare) {
            return new UpdateAvailabilityResult(
                $installedVersion,
                $targetVersion,
                true,
                true,
                false,
                false,
                'update_available',
                'An update is available.',
                'verify',
            );
        }

        if (0 === $compare) {
            return new UpdateAvailabilityResult(
                $installedVersion,
                $targetVersion,
                true,
                false,
                true,
                false,
                'up_to_date',
                'The installed version already matches the manifest version.',
                'done',
            );
        }

        return new UpdateAvailabilityResult(
            $installedVersion,
            $targetVersion,
            true,
            false,
            false,
            true,
            'downgrade_blocked',
            'The manifest version is older than the installed version. Downgrades are blocked by default.',
            'blocked',
        );
    }
}
