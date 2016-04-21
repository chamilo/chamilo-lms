<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\ContactBundle\Controller;

use Chamilo\ContactBundle\Entity\Category;
use Chamilo\ContactBundle\Form\Type\ContactType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ContactController
 * @package Chamilo\ContactBundle\Controller
 */
class ContactController extends Controller
{
    /**
     * @Route("/contact")
     *
     * @param Request $request
     * @return mixed
     */
    public function indexAction(Request $request)
    {
        $type = new ContactType();
        $form = $this->createForm($type);

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
                            'ContactBundle:contact.html.twig',
                            array(
                                'ip' => $request->getClientIp(),
                                'firstname' => $form->get('firstname')->getData(),
                                'lastname' => $form->get('lastname')->getData(),
                                'subject' => $form->get('subject')->getData(),
                                'message' => $form->get('message')->getData()
                            )
                        )
                    );

                $this->get('mailer')->send($message);

                $request->getSession()->getFlashBag()->add(
                    'success', 
                    'Your email has been sent! Thanks!'
                );

                return $this->redirect($this->generateUrl('index'));
            }
        }

        return $this->render(
            '@ContactBundle/index.html.twig',
            array(
                'form'       => $form->createView()
            )
        );
    }
}
