<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Dto;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use Chamilo\CoreBundle\Controller\Api\NotifyEnrollmentAction;
use Chamilo\CoreBundle\Entity\User;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Input for POST /api/enrollment-notifications — a standalone action, not tied to
 * any Doctrine entity, that explicitly triggers EnrollmentNotificationService.
 * ROLE_ADMIN-only, same as every other endpoint the WordPress storefront plugin's
 * service account uses. output: false — the response carries no body; the HTTP
 * status code alone (200 success, 4xx on a bad/unenrolled combination) is all a
 * caller needs.
 */
#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/enrollment-notifications',
            controller: NotifyEnrollmentAction::class,
            security: "is_granted('ROLE_ADMIN')",
            output: false,
            name: 'notify_enrollment',
        ),
    ],
    denormalizationContext: ['groups' => ['enrollment_notification:write']],
)]
class EnrollmentNotificationInput
{
    #[Assert\NotNull]
    #[Groups(['enrollment_notification:write'])]
    private ?User $user = null;

    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['course', 'session'])]
    #[Groups(['enrollment_notification:write'])]
    private string $itemType = '';

    #[Assert\Positive]
    #[Groups(['enrollment_notification:write'])]
    private int $itemId = 0;

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): void
    {
        $this->user = $user;
    }

    public function getItemType(): string
    {
        return $this->itemType;
    }

    public function setItemType(string $itemType): void
    {
        $this->itemType = $itemType;
    }

    public function getItemId(): int
    {
        return $this->itemId;
    }

    public function setItemId(int $itemId): void
    {
        $this->itemId = $itemId;
    }
}
