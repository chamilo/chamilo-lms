<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\AccessUrlScopeHelper;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Guards ROLE_GLOBAL_ADMIN grants on User create/update: only an unrestricted global admin
 * (registered in the topmost URL of a tree — AccessUrlScopeHelper::canGrantGlobalAdminRole())
 * may add the role, to themselves or to anyone else. Every other write passes through
 * unaffected; this only blocks the specific transition of newly gaining the role.
 *
 * Access-URL scoping of *who* may edit a given user at all (including their password) is
 * enforced upstream by UserVoter::EDIT (AccessUrlScopeHelper::canEditUser()) before this
 * processor ever runs, so it is not duplicated here.
 *
 * @implements ProcessorInterface<User, User>
 */
final readonly class UserRolesProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $persistProcessor,
        private EntityManagerInterface $em,
        private UserHelper $userHelper,
        private AccessUrlScopeHelper $accessUrlScope,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        \assert($data instanceof User);

        $hadRoleBefore = false;
        if (null !== $data->getId()) {
            // Compare against the persisted (pre-request) roles column, not the in-memory
            // entity — denormalization has already mutated it by the time this runs — so an
            // unrelated edit to an existing global admin is never blocked; only newly ADDING
            // the role is. This intentionally ignores group-inherited grants (no default group
            // carries ROLE_GLOBAL_ADMIN, and nothing in this codebase assigns users to one), so
            // it stays a simple raw-column comparison rather than re-deriving getRoles()'s full
            // persisted+temporary+group computation for a path that cannot occur today.
            $originalRoles = $this->em->getUnitOfWork()->getOriginalEntityData($data)['roles'] ?? [];
            $hadRoleBefore = \in_array('ROLE_GLOBAL_ADMIN', array_map('strtoupper', (array) $originalRoles), true);
        }

        $hasRoleNow = \in_array('ROLE_GLOBAL_ADMIN', $data->getRoles(), true);

        if ($hasRoleNow && !$hadRoleBefore) {
            $actor = $this->userHelper->getCurrent();
            if (null === $actor || !$this->accessUrlScope->canGrantGlobalAdminRole($actor)) {
                throw new AccessDeniedHttpException('Only a global admin registered in the topmost access URL may grant the global admin role.');
            }
        }

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}
