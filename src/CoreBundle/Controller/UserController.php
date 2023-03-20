<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Repository\Node\IllustrationRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @author Julio Montoya <gugli100@gmail.com>
 */
#[Route('/user')]
class UserController extends AbstractController
{
    /**
     * Public profile.
     *
     * @Route("/{username}", methods={"GET"}, name="chamilo_core_user_profile")
     */
    public function profileAction(string $username, UserRepository $userRepository, IllustrationRepository $illustrationRepository): Response
    {
        $user = $userRepository->findByUsername($username);

        if (!\is_object($user) || !$user instanceof UserInterface) {
            throw $this->createAccessDeniedException('This user does not have access to this section');
        }

        $url = $illustrationRepository->getIllustrationUrl($user);

        return $this->render('@ChamiloCore/User/profile.html.twig', [
            'user' => $user,
            'illustration_url' => $url,
        ]);
    }
}
