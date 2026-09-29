<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Update\Dto;

use Symfony\Component\DependencyInjection\Attribute\Exclude;

#[Exclude]
final readonly class UpdateAvailabilityResult
{
    public function __construct(
        private string $installedVersion,
        private string $targetVersion,
        private bool $comparable,
        private bool $updateAvailable,
        private bool $sameVersion,
        private bool $downgrade,
        private string $status,
        private string $message,
        private string $nextStep,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'installedVersion' => $this->installedVersion,
            'targetVersion' => $this->targetVersion,
            'comparable' => $this->comparable,
            'updateAvailable' => $this->updateAvailable,
            'sameVersion' => $this->sameVersion,
            'downgrade' => $this->downgrade,
            'status' => $this->status,
            'message' => $this->message,
            'nextStep' => $this->nextStep,
        ];
    }
}
