<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Service\Mailer;

use Chamilo\CoreBundle\Settings\SettingsManager;
use Symfony\Component\Mime\Email;

final readonly class MassMailingTransportSelector
{
    public function __construct(
        private SettingsManager $settingsManager
    ) {}

    public function getMainDsn(): string
    {
        $dsn = trim((string) $this->settingsManager->getSetting('mail.mailer_dsn'));

        return '' !== $dsn ? $dsn : 'null://null';
    }

    public function getMassMailingDsn(): ?string
    {
        $dsn = trim((string) $this->settingsManager->getSetting('mail.mailer_dsn_massmailing'));

        return '' !== $dsn && $dsn !== $this->getMainDsn() ? $dsn : null;
    }

    public function routeMassMailing(Email $email): void
    {
        if (null !== $this->getMassMailingDsn()) {
            // Symfony Mailer removes this selector before the message reaches the recipient.
            $email->getHeaders()->addTextHeader('X-Transport', 'massmailing');
        }
    }
}
