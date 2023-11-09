<?php
declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Form\ContactType;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;

class ContactController extends AbstractController
{
    /**
     * @Route("/contact2", name="contact2")
     */
    public function index(Request $request, MailerInterface $mailer, SettingsManager $settingsManager)
    {
        $form = $this->createForm(ContactType::class);
        $form->handleRequest($request);

        // Check if the form is submitted and valid
        if ($form->isSubmitted() && $form->isValid()) {
            // Get the data from the form
            $contactData = $form->getData();
            $toEmail = $settingsManager->getSetting('admin.administrator_email');

            // Create and send the email
            $email = (new Email())
                ->from($contactData['email'])
                ->to($toEmail)
                ->subject($contactData['subject'])
                ->text(
                    "Sender: {$contactData['email']}\n".
                    "Message: {$contactData['message']}"
                );

            // Send the email
            $mailer->send($email);

            // Add a flash message for user feedback
            $this->addFlash('success', 'Your message has been sent successfully!');

            // Redirect the user to the 'contact' route
            return $this->redirectToRoute('contact2');
        }

        // Render the form view
        return $this->render(
            '@ChamiloCore/Contact/index.html.twig',
            [
                'form' => $form->createView(),
            ]
        );
    }
}
