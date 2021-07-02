<?php
/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Entity\Listener;

use Chamilo\CoreBundle\Entity\SkillRelUser;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Display;
use Doctrine\ORM\Event\LifecycleEventArgs;
use MessageManager;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class SkillRelUserListener
 *
 * @package Chamilo\CoreBundle\Entity\Listener
 */
class SkillRelUserListener
{
    private SettingsManager $settingsManager;
    private RouterInterface $router;
    private TranslatorInterface $translator;

    public function __construct(
        SettingsManager $settingsManager,
        RouterInterface $router,
        TranslatorInterface $translator
    ) {
        $this->settingsManager = $settingsManager;
        $this->router = $router;
        $this->translator = $translator;
    }

    public function postPersist(SkillRelUser $skillRelUser, LifecycleEventArgs $event): void
    {
        $user = $skillRelUser->getUser();
        $skill = $skillRelUser->getSkill();

        $badgeAssignationNotification = $this->settingsManager->getSetting('skill.badge_assignation_notification');

        if ('true' === $badgeAssignationNotification) {
            $url = $this->router->generate(
                'badge_issued_all',
                ['skillId' => $skill->getId(), 'userId' => $user->getId()]
            );

            $message = sprintf(
                $this->translator->trans('Hi, %s. You have achieved the skill "%s". To see the details go here: %s.'),
                $user->getFirstname(),
                $skill->getName(),
                Display::url($url, $url)
            );

            MessageManager::send_message_simple(
                $user->getId(),
                $this->translator->trans('You have achieved a new skillskill.'),
                $message
            );
        }
    }
}
