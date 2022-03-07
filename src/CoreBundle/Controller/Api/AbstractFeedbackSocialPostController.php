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

#[AsController]
abstract class AbstractFeedbackSocialPostController extends AbstractController
{
    public function __construct(
        protected Security $security,
        protected EntityManager $entityManager,
        protected SettingsManager $settingsManager
    ) {
        if ('true' !== $this->settingsManager->getSetting('social.allow_social_tool')) {
            throw new AccessDeniedException();
        }
    }

    protected function getFeedbackForCurrentUser(SocialPost $socialPost): SocialPostFeedback
    {
        /** @var User $user */
        $user = $this->security->getUser();

        $feedback = $this->entityManager
            ->getRepository(SocialPostFeedback::class)
            ->findOneBy(
                [
                    'user' => $user,
                    'socialPost' => $socialPost,
                ]
            )
        ;

        if (null === $feedback) {
            $feedback = (new SocialPostFeedback())->setUser($user);

            $socialPost->addFeedback($feedback);
        }

        return $feedback;
    }
}
