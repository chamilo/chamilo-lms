<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Entity\SocialPost;
use Chamilo\CoreBundle\Entity\SocialPostFeedback;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;

#[AsController]
abstract class AbstractFeedbackSocialPostController extends AbstractController
{
    protected User|UserInterface $currentUser;

    public function __construct(
        protected Security $security,
        protected EntityManager $entityManager,
        protected SettingsManager $settingsManager
    ) {
        $this->currentUser = $this->security->getUser();

        if ('true' !== $this->settingsManager->getSetting('social.allow_social_tool')) {
            throw new AccessDeniedException();
        }
    }

    protected function getFeedbackForCurrentUser(SocialPost $socialPost): SocialPostFeedback
    {
        $feedback = $this->entityManager
            ->getRepository(SocialPostFeedback::class)
            ->findOneBy(
                [
                    'user' => $this->currentUser,
                    'socialPost' => $socialPost,
                ]
            )
        ;

        if (null === $feedback) {
            $feedback = (new SocialPostFeedback())->setUser($this->currentUser);

            $socialPost->addFeedback($feedback);
        }

        return $feedback;
    }
}
