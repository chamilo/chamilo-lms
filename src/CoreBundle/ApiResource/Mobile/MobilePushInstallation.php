<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Mobile;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Post;
use Chamilo\CoreBundle\Entity\MobilePushInstallation as MobilePushInstallationEntity;
use Chamilo\CoreBundle\State\Mobile\MobilePushInstallationProcessor;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

use const DATE_ATOM;

#[ApiResource(
    shortName: 'MobilePushInstallation',
    operations: [
        new Post(
            uriTemplate: '/mobile_push_installations',
            status: Response::HTTP_OK,
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            name: 'register_mobile_push_installation',
            processor: MobilePushInstallationProcessor::class,
        ),
        new Delete(
            uriTemplate: '/mobile_push_installations/{installationId}',
            requirements: ['installationId' => '[0-9a-fA-F-]{36}'],
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            input: false,
            output: false,
            read: false,
            name: 'remove_mobile_push_installation',
            processor: MobilePushInstallationProcessor::class,
        ),
    ],
    normalizationContext: ['groups' => ['mobile_push_installation:read']],
    denormalizationContext: ['groups' => ['mobile_push_installation:write']],
)]
final class MobilePushInstallation
{
    public const string PLATFORM_ANDROID = 'android';

    #[ApiProperty(identifier: true)]
    #[Groups(['mobile_push_installation:read', 'mobile_push_installation:write'])]
    #[Assert\NotBlank]
    #[Assert\Uuid]
    public string $installationId = '';

    #[Groups(['mobile_push_installation:write'])]
    #[Assert\NotBlank(normalizer: 'trim')]
    #[Assert\Length(max: 4096)]
    public string $token = '';

    #[Groups(['mobile_push_installation:read', 'mobile_push_installation:write'])]
    #[Assert\NotBlank]
    #[Assert\Choice(choices: [self::PLATFORM_ANDROID])]
    public string $platform = self::PLATFORM_ANDROID;

    #[Groups(['mobile_push_installation:read'])]
    public ?string $createdAt = null;

    #[Groups(['mobile_push_installation:read'])]
    public ?string $lastSeenAt = null;

    public static function fromEntity(MobilePushInstallationEntity $installation): self
    {
        $resource = new self();
        $resource->installationId = $installation->getInstallationId();
        $resource->platform = $installation->getPlatform();
        $resource->createdAt = $installation->getCreatedAt()->format(DATE_ATOM);
        $resource->lastSeenAt = $installation->getLastSeenAt()->format(DATE_ATOM);

        return $resource;
    }
}
