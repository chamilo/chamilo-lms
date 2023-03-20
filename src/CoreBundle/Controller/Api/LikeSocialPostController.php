<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Entity\SocialPost;

class LikeSocialPostController extends AbstractFeedbackSocialPostController
{
    public function __invoke(SocialPost $socialPost): SocialPost
    {
        $feedback = $this->getFeedbackForCurrentUser($socialPost);
        $feedback
            ->setDisliked(false)
            ->setLiked(
                !$feedback->isLiked()
            )
        ;

        $this->entityManager->persist($feedback);
        $this->entityManager->flush();

        return $socialPost;
    }
}
