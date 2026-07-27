<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Mobile;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\State\Mobile\MobileMessageRecipientProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'MobileMessageRecipient',
    operations: [
        new GetCollection(
            uriTemplate: '/mobile_message_recipients',
            paginationEnabled: false,
            security: "is_granted('ROLE_USER')",
            name: 'get_mobile_message_recipients',
            provider: MobileMessageRecipientProvider::class,
        ),
    ],
    normalizationContext: ['groups' => ['mobile_message_recipient:read']],
)]
final class MobileMessageRecipient
{
    #[ApiProperty(identifier: true)]
    #[Groups(['mobile_message_recipient:read'])]
    public int $id;

    #[Groups(['mobile_message_recipient:read'])]
    public string $username;

    #[Groups(['mobile_message_recipient:read'])]
    public string $fullName;

    public static function fromUser(User $user): self
    {
        $resource = new self();
        $resource->id = (int) $user->getId();
        $resource->username = $user->getUsername();
        $resource->fullName = $user->getFullName() ?: $user->getUsername();

        return $resource;
    }
}
