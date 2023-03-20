<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Entity\SocialPost;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Security;

class DislikeSocialPostController extends AbstractFeedbackSocialPostController
{
    public function __construct(Security $security, EntityManager $entityManager, SettingsManager $settingsManager)
    {
        parent::__construct($security, $entityManager, $settingsManager);

        if ('false' !== $this->settingsManager->getSetting('social.disable_dislike_option', true)) {
            throw new AccessDeniedException();
        }
    }

    public function __invoke(SocialPost $socialPost): SocialPost
    {
        $feedback = $this->getFeedbackForCurrentUser($socialPost);
        $feedback
            ->setLiked(false)
            ->setDisliked(
                !$feedback->isDisliked()
            )
        ;

        $this->entityManager->persist($feedback);
        $this->entityManager->flush();

        return $socialPost;
    }
}
