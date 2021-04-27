<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\SerializerInterface;

class SecurityController extends AbstractController
{
    private SerializerInterface $serializer;

    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * Route("/login", name="login").
     */
    //public function login(AuthenticationUtils $authenticationUtils): Response
    //{
    //$error = $authenticationUtils->getLastAuthenticationError();
    //$lastUsername = $authenticationUtils->getLastUsername();

    /** @var User */
    /*$user = $this->getUser();
    $data = [];
    if ($user) {
        $userClone = clone $user;
        $userClone->setPassword('');
        $data = $this->serializer->serialize($userClone, JsonEncoder::FORMAT);
    }

    return new JsonResponse($data, Response::HTTP_OK, [], true);*/
    //}

    /**
     * @Route("/login_json", name="login_json")
     */
    public function loginJson(AuthenticationUtils $authenticationUtils): Response
    {
        //$error = $authenticationUtils->getLastAuthenticationError();
        //$lastUsername = $authenticationUtils->getLastUsername();

        /** @var User $user */
        $user = $this->getUser();
        $data = null;
        if ($user) {
            $userClone = clone $user;
            $userClone->setPassword('');
            $data = $this->serializer->serialize($userClone, JsonEncoder::FORMAT);
        }

        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }
}
