<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Entity\Listener;

use Chamilo\CoreBundle\Entity\Message;
use Chamilo\CoreBundle\Entity\SkillRelUser;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Display;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

class SkillRelUserListener
{
    public function __construct(
        private SettingsManager $settingsManager,
        private RouterInterface $router,
        private TranslatorInterface $translator,
        protected Security $security
    ) {
    }

    public function postPersist(SkillRelUser $skillRelUser, PostPersistEventArgs $event): void
    {
        $user = $skillRelUser->getUser();
        $skill = $skillRelUser->getSkill();

        // Notification of badge assignation
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

        if (null !== $this->security->getToken()) {
            /** @var User $currentUser */
            $currentUser = $this->security->getUser();
            $message = (new Message())
                ->setTitle($this->translator->trans('You have achieved a new skill.'))
                ->setContent($message)
                ->addReceiverTo($user)
                ->setSender($currentUser)
            ;

            $event->getObjectManager()->persist($message);
            $event->getObjectManager()->flush();
        }
    }
}
