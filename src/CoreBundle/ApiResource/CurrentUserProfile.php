<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Model\Operation;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\State\CurrentUserProfileStateProvider;
use RuntimeException;

#[ApiResource(
    shortName: 'CurrentUserProfile',
    operations: [
        new Get(
            uriTemplate: '/me',
            openapi: new Operation(
                summary: 'Get the authenticated user profile for the current access URL.'
            ),
            security: "is_granted('ROLE_USER')",
            name: 'current_user_profile',
            provider: CurrentUserProfileStateProvider::class,
        ),
    ],
)]
final readonly class CurrentUserProfile
{
    /**
     * @param string[] $roles
     */
    public function __construct(
        #[ApiProperty(identifier: false)]
        public int $id,
        public string $username,
        public ?string $firstname,
        public ?string $lastname,
        public string $fullName,
        public string $email,
        public string $locale,
        public string $timezone,
        public array $roles,
    ) {}

    public static function fromUser(User $user): self
    {
        $id = $user->getId();
        if (null === $id) {
            throw new RuntimeException('Authenticated user has no identifier.');
        }

        $roles = $user->getRoles();
        sort($roles);

        $fullName = trim($user->getFullName());
        if ('' === $fullName) {
            $fullName = $user->getUsername();
        }

        return new self(
            id: $id,
            username: $user->getUsername(),
            firstname: $user->getFirstname(),
            lastname: $user->getLastname(),
            fullName: $fullName,
            email: $user->getEmail(),
            locale: $user->getLocale(),
            timezone: $user->getTimezone(),
            roles: $roles,
        );
    }

    #[ApiProperty(readable: false, writable: false, identifier: true)]
    public function getResourceIdentifier(): string
    {
        return 'current-user';
    }
}
