<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Security\Authorization\Voter;

use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelUser;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\SessionCatalogueSubscriptionHelper;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Creation rules for a session subscription.
 *
 * Outside of administration, the only legitimate creation is a user subscribing
 * themselves as a student from the session catalogue, which the platform has to
 * allow: the catalogue hides its button when the setting is off, and that
 * decision cannot be left to the browser, and neither can the sale, catalogue and
 * prerequisite rules of SessionCatalogueSubscriptionHelper.
 *
 * @extends Voter<'CREATE', SessionRelUser>
 */
class SessionRelUserVoter extends Voter
{
    public const CREATE = 'CREATE';

    public function __construct(
        private AccessDecisionManagerInterface $accessDecisionManager,
        private SettingsManager $settingsManager,
        private SessionCatalogueSubscriptionHelper $catalogueSubscriptionHelper,
    ) {}

    protected function supports(string $attribute, $subject): bool
    {
        return self::CREATE === $attribute && $subject instanceof SessionRelUser;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        if ($this->accessDecisionManager->decide($token, ['ROLE_ADMIN'])) {
            return true;
        }

        $currentUser = $token->getUser();

        if (!$currentUser instanceof User) {
            return false;
        }

        if (!$this->isAutoSubscriptionAllowed()) {
            return false;
        }

        /** @var SessionRelUser $subject */
        return $subject->getUser()->getId() === $currentUser->getId()
            && Session::STUDENT === $subject->getRelationType()
            && $this->catalogueSubscriptionHelper->canSelfSubscribe($subject->getSession(), $currentUser);
    }

    private function isAutoSubscriptionAllowed(): bool
    {
        $setting = $this->settingsManager->getSetting('catalog.allow_session_auto_subscription', true);

        return 'true' === strtolower((string) $setting);
    }
}
