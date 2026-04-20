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
 * Decorates the API Platform serializer context builder for User resources.
 *
 * Normalization (GET collection):
 *   Admins, teachers and session managers keep the full "user:read" group.
 *   All other roles receive only "user:read:public" (id/username/name/photo).
 *
 * Denormalization (PUT):
 *   The "roles" field belongs to the "user:admin:write" group and is excluded
 *   from the standard "user:write" group. This builder adds "user:admin:write"
 *   to the denormalization context only when the current user is admin,
 *   preventing privilege escalation via the PUT /api/users/{id} endpoint.
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

        if (($context['resource_class'] ?? null) !== User::class) {
            return $context;
        }

        if ($normalization) {
            return $this->applyNormalizationContext($context);
        }

        return $this->applyDenormalizationContext($context);
    }

    private function isPrivileged(): bool
    {
        return $this->security->isGranted('ROLE_ADMIN')
            || $this->security->isGranted('ROLE_SUPER_ADMIN')
            || $this->security->isGranted('ROLE_GLOBAL_ADMIN');
    }

    /**
     * Restrict collection responses to public fields for unprivileged users.
     */
    private function applyNormalizationContext(array $context): array
    {
        $operation = $context['operation'] ?? null;
        if (!$operation instanceof CollectionOperationInterface) {
            return $context;
        }

        if (
            $this->isPrivileged()
            || $this->security->isGranted('ROLE_TEACHER')
            || $this->security->isGranted('ROLE_SESSION_MANAGER')
        ) {
            return $context;
        }

        $context['groups'] = ['user:read:public'];

        return $context;
    }

    /**
     * Allow writing the "roles" field only when the current user is admin.
     */
    private function applyDenormalizationContext(array $context): array
    {
        if (!$this->isPrivileged()) {
            return $context;
        }

        $context['groups'] = array_merge($context['groups'] ?? [], ['user:admin:write']);

        return $context;
    }
}
