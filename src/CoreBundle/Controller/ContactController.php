<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Form\ContactType;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;

class ContactController extends AbstractController
{
    #[Route(path: '/contact', name: 'contact')]
    public function index(Request $request, MailerInterface $mailer, SettingsManager $settingsManager)
    {
        $form = $this->createForm(ContactType::class);
        $termsContent = $form->getConfig()->getOption('terms_content');
        $form->handleRequest($request);

        // Check if the form is submitted and valid
        if ($form->isSubmitted() && $form->isValid()) {
            // Get the data from the form
            $contactData = $form->getData();
            $category = $contactData['category'];
            $toEmail = $category->getEmail();

            // Create and send the email
            $email = (new Email())
                ->from($contactData['email'])
                ->to($toEmail)
                ->subject($contactData['subject'])
                ->text(
                    "Sender: {$contactData['email']}\n".
                    "Message: {$contactData['message']}"
                )
            ;

            // Send the email
            $mailer->send($email);

            // Add a flash message for user feedback
            $this->addFlash('success', 'Your message has been sent successfully!');

            // Redirect the user to the 'contact' route
            return $this->redirectToRoute('contact');
        }

        // Render the form view
        return $this->render(
            '@ChamiloCore/Contact/index.html.twig',
            [
                'form' => $form,
                'termsContent' => $termsContent,
            ]
        );
    }
}
