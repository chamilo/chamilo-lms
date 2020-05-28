<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Class SecurityController.
 */
class SecurityController extends AbstractController
{
    /** @var SerializerInterface */
    private $serializer;

    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * @Route("/login", name="login")
     */
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        $user = $this->getUser();
        $data = null;
        if (!empty($user)) {
            $userClone = clone $user;
            $userClone->setPassword('');
            $data = $this->serializer->serialize($userClone, JsonEncoder::FORMAT);
        }

        return $this->render('@ChamiloCore/Index/vue.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
        /*return $this->render('@ChamiloCore/login.html.twig', [
            'last_username' => $lastUsername,
            'is_authenticated' => json_encode(!empty($this->getUser())),
            'user' => $data ?? json_encode($data),
            'error' => $error,
        ]);*/
    }

    /**
     * @Route("/login_json", name="login_json")
     */
    public function loginJson(AuthenticationUtils $authenticationUtils): Response
    {
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        $user = $this->getUser();
        $data = null;
        if (!empty($user)) {
            $userClone = clone $user;
            $userClone->setPassword('');
            $data = $this->serializer->serialize($userClone, JsonEncoder::FORMAT);
        }

        /*return $this->render('@ChamiloCore/Index/courses.html.twig', [
            'last_username' => $lastUsername,
            'is_authenticated' => json_encode(!empty($this->getUser())),
            'error' => $error,
            'isAuthenticated' => json_encode(!empty($user)),
            'user' => $data ?? json_encode($data),
        ]);*/

        return $this->render('@ChamiloCore/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }
}
