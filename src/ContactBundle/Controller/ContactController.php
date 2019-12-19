<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\ContactBundle\Controller;

use Chamilo\ContactBundle\Entity\Category;
use Chamilo\ContactBundle\Form\Type\ContactType;
use Chamilo\UserBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class ContactController.
 *
 * @Route("/")
 */
class ContactController extends AbstractController
{
    /**
     * @Route("/", name="contact")
     */
    public function indexAction(Request $request, MailerInterface $mailer)
    {
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

        $form = $this->createForm(ContactType::class, $data);

        if ($request->isMethod('POST')) {
            $form->bind($request);

            $em = $this->getDoctrine()->getManager();

            $category = $form->get('category')->getData();
            /** @var Category $category */
            $category = $em->getRepository('ChamiloContactBundle:Category')->find($category);

            if ($form->isValid()) {
                $message = new Email();
                $message->subject($form->get('subject')->getData())
                    ->from($form->get('email')->getData())
                    ->to($category->getEmail())
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

                $mailer->send($message);

                $this->addFlash(
                    'success',
                    $this->get('translator')->trans('Your email has been sent! Thanks!')
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
