<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\EventListener;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Security\CourseAccessResolver;
use Chamilo\CourseBundle\Entity\CGroup;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Authentication\Token\JWTUserToken;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Authenticator\Token\JWTPostAuthenticationToken;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken;

/**
 * Publishes the contextual ROLE_CURRENT_COURSE_* roles for the authenticated
 * user once CidReqListener has resolved the course/session/group context.
 *
 * The roles are exposed in two places so that every consumer keeps working:
 * - User::$temporaryRoles, so ResourceNodeVoter::hasContextRole() and any
 *   code reading $user->getRoles() continues to see them.
 * - The security token's roleNames, so expressions such as
 *   `security: "is_granted('ROLE_CURRENT_COURSE_STUDENT')"` on
 *   API Platform operations resolve correctly (AbstractToken::getRoleNames()
 *   freezes the role list at token-creation time).
 *
 * Must run with a kernel.request priority lower than CidReqListener (priority 6)
 * so the course/session/group is already in the session by the time we look it up.
 */
final class CourseContextRoleListener
{
    public const string JWT_AUTHENTICATED_REQUEST_ATTRIBUTE = '_chamilo_jwt_authenticated';

    public function __construct(
        private readonly TokenStorageInterface $tokenStorage,
        private readonly CourseAccessResolver $resolver,
    ) {}

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $token = $this->tokenStorage->getToken();

        if ($token instanceof JWTPostAuthenticationToken || $token instanceof JWTUserToken) {
            $request->attributes->set(self::JWT_AUTHENTICATED_REQUEST_ATTRIBUTE, true);
        }

        if (!$request->hasSession() || null === $token) {
            return;
        }

        $user = $token->getUser();
        if (!$user instanceof User) {
            return;
        }

        $sessionHandler = $request->getSession();
        $course = $sessionHandler->get('course');
        $courseSession = $sessionHandler->get('session');
        $group = $sessionHandler->get('group');
        $contextRoles = [];

        if ($course instanceof Course) {
            $contextRoles = $this->resolver->resolveCourseRoles(
                $user,
                $course,
                $courseSession instanceof Session ? $courseSession : null,
            );

            if ($group instanceof CGroup) {
                $contextRoles = array_merge(
                    $contextRoles,
                    $this->resolver->resolveGroupRoles($user, $course, $group),
                );
            }
        }

        $contextRoles = array_values(array_unique($contextRoles));
        $existingRoleNames = $token->getRoleNames();
        $hasStaleContextRoles = [] !== array_intersect($existingRoleNames, User::CONTEXT_ROLES);

        if ([] === $contextRoles && !$hasStaleContextRoles) {
            return;
        }

        foreach ($contextRoles as $role) {
            $user->addTemporaryRole($role);
        }

        $persistedRoleNames = array_values(array_diff($existingRoleNames, User::CONTEXT_ROLES));
        $desiredRoleNames = array_values(array_unique(array_merge($persistedRoleNames, $contextRoles)));
        $firewallName = method_exists($token, 'getFirewallName')
            ? (string) $token->getFirewallName()
            : 'main';

        if ('' === $firewallName) {
            $firewallName = 'main';
        }

        $this->tokenStorage->setToken(new PostAuthenticationToken($user, $firewallName, $desiredRoleNames));
    }
}
