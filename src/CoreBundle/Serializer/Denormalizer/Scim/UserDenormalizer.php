<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Serializer\Denormalizer\Scim;

use Chamilo\CoreBundle\Entity\User;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class UserDenormalizer implements DenormalizerInterface, DenormalizerAwareInterface
{
    use DenormalizerAwareTrait;

    public const FORMAT = 'scim';
    private const ALREADY_CALLED = 'SCIM_USER_DENORMALIZER_ALREADY_CALLED';

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = [])
    {
        $context[self::ALREADY_CALLED] = true;

        $user = $context['object_to_populate'] ?? new User();

        if (isset($data['userName'])) {
            $user->setUsername($data['userName']);
        }

        if (isset($data['active'])) {
            $user->setActive((int) $data['active']);
        }

        if (isset($data['name'])) {
            $name = $data['name'];

            if (isset($name['givenName'])) {
                $user->setFirstName($name['givenName']);
            }

            if (isset($name['familyName'])) {
                $user->setLastName($name['familyName']);
            }
        }

        if ($email = self::getPrimaryValue($data, 'emails')) {
            $user->setEmail($email);
        } else {
            $user->setEmail($data['userName'] ?? '');
        }

        if ($phone = self::getPrimaryValue($data, 'phoneNumbers')) {
            $user->setPhone($phone);
        }

        if ($address = self::getPrimaryValue($data, 'addresses', 'formatted')) {
            $user->setAddress($address);
        }

        if (isset($data['locale'])) {
            $user->setLocale(substr($data['locale'], 0, 10));
        }

        if (isset($data['timezone'])) {
            $user->setTimezone($data['timezone']);
        }

        return $user;
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        if (isset($context[self::ALREADY_CALLED])) {
            return false;
        }

        return User::class === $type
            && \is_array($data)
            && self::FORMAT === $format;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            User::class => false,
        ];
    }

    public static function getPrimaryValue(array $values, string $propertyName, string $subPropertyName = 'value'): mixed
    {
        if (!isset($values[$propertyName]) || !\is_array($values[$propertyName])) {
            return null;
        }

        foreach ($values[$propertyName] as $value) {
            if (!empty($value['primary']) && true === $value['primary']) {
                return $value[$subPropertyName];
            }
        }

        if (!empty($values[$propertyName][0][$subPropertyName])) {
            return $values[$propertyName][0][$subPropertyName];
        }

        return null;
    }
}
