<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Installer;

/**
 * The state the web installer finds the platform in.
 */
enum InstallerState: string
{
    /**
     * No .env, or APP_INSTALLED is not 1. A first install, or an upgrade from 1.11.x
     * into a new code tree.
     */
    case FreshInstall = 'fresh_install';

    /**
     * APP_INSTALLED is 1 but the database holds no configuration rows. The installer
     * wrote the flag and then stopped, so the wizard must stay reachable to finish.
     */
    case Unfinished = 'unfinished';

    /**
     * The platform is installed and the code tree carries migrations it has not
     * executed yet. This is the 2.x to 3.x upgrade, and also a 1.11.x database, whose
     * whole migration history is pending by definition.
     */
    case UpgradePending = 'upgrade_pending';

    /**
     * The platform is installed and has migrations pending, but the administrator has
     * not authorised the upgrade: the flag file is absent from the project root.
     */
    case UpgradeNotAuthorised = 'upgrade_not_authorised';

    /**
     * The platform is installed and nothing is pending. The installer must refuse
     * the request: it has no authentication of its own.
     */
    case UpToDate = 'up_to_date';

    /**
     * Tells whether this state must close the installer endpoints.
     */
    public function isLocked(): bool
    {
        return self::UpToDate === $this || self::UpgradeNotAuthorised === $this;
    }

    /**
     * Tells whether this state must offer the upgrade path instead of a first install.
     */
    public function isUpgrade(): bool
    {
        return self::UpgradePending === $this;
    }
}
