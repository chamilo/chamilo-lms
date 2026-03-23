<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Serializer;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\State\SerializerContextBuilderInterface;
use Chamilo\CoreBundle\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;

/**
 * Decorates the API Platform serializer context builder to restrict the
 * fields returned by GET /api/users (collection) for unprivileged users.
 *
 * Admins, teachers and session managers keep the full "user:read" group.
 * All other authenticated roles receive only "user:read:public", which
 * exposes id, username, firstname, lastname and illustrationUrl — never
 * email, phone, roles or address.
 */
final readonly class UserSerializerContextBuilder implements SerializerContextBuilderInterface
{
    public function __construct(
        private SerializerContextBuilderInterface $decorated,
        private Security $security,
    ) {}

    public function createFromRequest(Request $request, bool $normalization, ?array $extractedAttributes = null): array
    {
        $context = $this->decorated->createFromRequest($request, $normalization, $extractedAttributes);

        if (!$normalization) {
            return $context;
        }

        if (($context['resource_class'] ?? null) !== User::class) {
            return $context;
        }

        $operation = $context['operation'] ?? null;
        if (!$operation instanceof CollectionOperationInterface) {
            return $context;
        }

        if (
            $this->security->isGranted('ROLE_ADMIN')
            || $this->security->isGranted('ROLE_SUPER_ADMIN')
            || $this->security->isGranted('ROLE_GLOBAL_ADMIN')
            || $this->security->isGranted('ROLE_TEACHER')
            || $this->security->isGranted('ROLE_SESSION_MANAGER')
        ) {
            return $context;
        }

        $context['groups'] = ['user:read:public'];

        return $context;
    }
}
