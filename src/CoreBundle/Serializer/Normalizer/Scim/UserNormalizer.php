<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Serializer\Normalizer\Scim;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\NameConventionHelper;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class UserNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    public const FORMAT = 'scim';
    private const ALREADY_CALLED = 'SCIM_USER_NORMALIZER_ALREADY_CALLED';

    public function __construct(
        private readonly NameConventionHelper $nameConventionHelper,
        private readonly UrlGeneratorInterface $router,
    ) {}

    public function normalize(mixed $object, ?string $format = null, array $context = []): array
    {
        /** @var User $user */
        $user = $object;

        $userInfo = [
            'schemas' => ['urn:ietf:params:scim:schemas:core:2.0:User'],
            'id' => $user->getResourceNode()->getUuid(),
            // 'externalId' => $user->getAzureId(),
            'userName' => $user->getUsername(),
            'name' => [
                'formatted' => $this->nameConventionHelper->getPersonName($user),
                'givenName' => $user->getFirstname(),
                'familyName' => $user->getLastName(),
            ],
            'emails' => [
                [
                    'value' => $user->getEmail(),
                    'type' => 'work',
                    'primary' => true,
                ],
            ],
            'active' => (bool) $user->isEnabled(),
            'meta' => [
                'resourceType' => 'User',
                'created' => $user->getCreatedAt()?->format('c'),
                'lastModified' => $user->getUpdatedAt()?->format('c'),
                'location' => $this->router->generate(
                    'scim_user',
                    ['uuid' => $user->getResourceNode()->getUuid()],
                    UrlGeneratorInterface::ABSOLUTE_URL
                ),
            ],
        ];

        if ($phone = $user->getPhone()) {
            $userInfo['phoneNumbers'] = [
                [
                    'type' => 'work',
                    'value' => $phone,
                    'display' => $phone,
                ],
            ];
        }

        if ($address = $user->getAddress()) {
            $userInfo['addresses'] = [
                [
                    'type' => 'work',
                    'formatted' => $address,
                ]
            ];
        }

        if ($timezone = $user->getTimezone()) {
            $userInfo['timezone'] = $timezone;
        }

        return $userInfo;
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        if (isset($context[self::ALREADY_CALLED])) {
            return false;
        }

        return $data instanceof User && self::FORMAT === $format;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            User::class => false,
        ];
    }
}
