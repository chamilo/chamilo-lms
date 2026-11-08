<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Helpers;

use BuyCoursesPlugin;
use Chamilo\CoreBundle\Entity\CatalogueSessionRelAccessUrlRelUsergroup;
use Chamilo\CoreBundle\Entity\SequenceResource;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\UsergroupRelUser;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Server-side version of the rules the session catalogue only applies in the browser, so that a
 * learner cannot self-subscribe to a session the catalogue would not let them subscribe to.
 */
final class SessionCatalogueSubscriptionHelper
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly AccessUrlHelper $accessUrlHelper,
    ) {}

    public function canSelfSubscribe(Session $session, User $user): bool
    {
        return $this->isInCatalogue($session, $user)
            && !$this->isForSale($session)
            && $this->hasCompletedRequirements($session, $user);
    }

    /**
     * Same selection as the catalogue listing: every session, unless the access URL restricts
     * the catalogue to some sessions (optionally for given user groups only).
     */
    private function isInCatalogue(Session $session, User $user): bool
    {
        $relations = $this->em->getRepository(CatalogueSessionRelAccessUrlRelUsergroup::class)->findBy([
            'accessUrl' => $this->accessUrlHelper->getCurrent(),
        ]);

        if ([] === $relations) {
            return true;
        }

        $userGroupIds = array_map(
            static fn (UsergroupRelUser $rel): ?int => $rel->getUsergroup()->getId(),
            $this->em->getRepository(UsergroupRelUser::class)->findBy(['user' => $user])
        );

        foreach ($relations as $relation) {
            if ($relation->getSession()->getId() !== $session->getId()) {
                continue;
            }

            $usergroup = $relation->getUsergroup();
            if (null === $usergroup || \in_array($usergroup->getId(), $userGroupIds, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * The catalogue shows a buy button instead of the subscribe one for these sessions.
     */
    private function isForSale(Session $session): bool
    {
        $buyData = BuyCoursesPlugin::create()->getBuyCoursePluginPrice($session);

        return !empty($buyData['buy_button']);
    }

    private function hasCompletedRequirements(Session $session, User $user): bool
    {
        $repository = $this->em->getRepository(SequenceResource::class);
        $sequences = $repository->getRequirements((int) $session->getId(), SequenceResource::SESSION_TYPE);

        if ([] === $sequences) {
            return true;
        }

        $requirementsData = $repository->checkRequirementsForUser(
            $sequences,
            SequenceResource::SESSION_TYPE,
            (int) $user->getId()
        );

        return $repository->checkSequenceAreCompleted($requirementsData);
    }
}
