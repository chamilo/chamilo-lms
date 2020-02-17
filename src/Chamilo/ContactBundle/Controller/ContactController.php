<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\ContactBundle\Controller;

use Chamilo\ContactBundle\Entity\Category;
use Chamilo\ContactBundle\Form\Type\ContactType;
use Chamilo\UserBundle\Entity\User;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ContactController.
 *
 * @Route("/")
 *
 * @package Chamilo\ContactBundle\Controller
 */
class ContactController extends Controller
{
    /**
     * @Route("/", name="contact")
     *
     * @return mixed
     */
    public function indexAction(Request $request)
    {
        $type = new ContactType();
        /** @var User $user */
        $user = $this->getUser();
        $data = [];

        if ($user) {
            $data = [
                'firstname' => $user->getFirstname(),
                'lastname' => $user->getFirstname(),
                'email' => $user->getEmail(),
            ];
        }

        $form = $this->createForm($type, $data);

        if ($request->isMethod('POST')) {
            $form->bind($request);

            $em = $this->getDoctrine()->getManager();

            $category = $form->get('category')->getData();
            /** @var Category $category */
            $category = $em->getRepository('ChamiloContactBundle:Category')->find($category);

            if ($form->isValid()) {
                $message = \Swift_Message::newInstance()
                    ->setSubject($form->get('subject')->getData())
                    ->setFrom($form->get('email')->getData())
                    ->setTo($category->getEmail())
                    ->setBody(
                        $this->renderView(
                            '@ChamiloContact/contact.html.twig',
                            [
                                'ip' => $request->getClientIp(),
                                'firstname' => $form->get('firstname')->getData(),
                                'lastname' => $form->get('lastname')->getData(),
                                'subject' => $form->get('subject')->getData(),
                                'email' => $form->get('email')->getData(),
                                'message' => $form->get('message')->getData(),
                            ]
                        )
                    );

                $this->get('mailer')->send($message);
                $this->addFlash(
                    'success',
                    'Your email has been sent! Thanks!'
                );

                return $this->redirect($this->generateUrl('contact'));
            }
        }

        return $this->render(
            '@ChamiloContact/index.html.twig',
            [
                'form' => $form->createView(),
            ]
        );
    }
}
