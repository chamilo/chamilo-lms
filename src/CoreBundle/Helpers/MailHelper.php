<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Helpers;

use Chamilo\CoreBundle\Settings\SettingsManager;
use Exception;
use Notification;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\BodyRendererInterface;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class MailHelper
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly BodyRendererInterface $bodyRenderer,
        private readonly ThemeHelper $themeHelper,
        private readonly ValidatorInterface $validator,
        private readonly SettingsManager $settingsManager,
    ) {}

    private function setNoreplyAndFromAddress(
        TemplatedEmail $email,
        array $sender,
        array $replyToAddress = []
    ): void {
        $emailConstraint = new Assert\Email();

        // Default values
        $notification = new Notification();
        $defaultSenderName = $notification->getDefaultPlatformSenderName();
        $defaultSenderEmail = $notification->getDefaultPlatformSenderEmail();

        // If the parameter is set, don't use the admin.
        $senderName = !empty($sender['name']) ? $sender['name'] : $defaultSenderName;
        $senderEmail = !empty($sender['email']) ? $sender['email'] : $defaultSenderEmail;

        // Send errors to the platform admin
        $adminEmail = $this->settingsManager->getSetting('admin.administrator_email');

        $adminEmailValidation = $this->validator->validate($adminEmail, $emailConstraint);

        if (!empty($adminEmail) && 0 === $adminEmailValidation->count()) {
            $email
                ->getHeaders()
                ->addIdHeader('Errors-To', $adminEmail)
            ;
        }

        // Reply to first
        if (!empty($replyToAddress) && isset($replyToAddress['mail']) && isset($replyToAddress['name'])) {
            $replyToEmailValidation = $this->validator->validate($replyToAddress['mail'], $emailConstraint);

            if (0 === $replyToEmailValidation->count()) {
                $email->addReplyTo(new Address($replyToAddress['mail'], $replyToAddress['name']));
            }
        }

        $email->from(new Address($senderEmail, $senderName));
    }

    public function send(
        string $recipientName,
        string $recipientEmail,
        string $subject,
        string $body,
        ?string $senderName = null,
        ?string $senderEmail = null,
        array $extra_headers = [],
        array $data_file = [],
        bool $embeddedImage = false,
        array $additionalParameters = [],
        ?string $sendErrorTo = null,
    ): bool {
        if (!api_valid_email($recipientEmail)) {
            return false;
        }

        $templatedEmail = new TemplatedEmail();

        $this->setNoreplyAndFromAddress(
            $templatedEmail,
            ['name' => $senderName, 'email' => $senderEmail],
            !empty($extra_headers['reply_to']) ? $extra_headers['reply_to'] : []
        );

        if ($sendErrorTo) {
            $templatedEmail
                ->getHeaders()
                ->addIdHeader('Errors-To', $sendErrorTo)
            ;
        }

        // Reply to first
        $replyToName = '';
        $replyToEmail = '';
        if (isset($extra_headers['reply_to'])) {
            $replyToEmail = $extra_headers['reply_to']['mail'];
            $replyToName = $extra_headers['reply_to']['name'];
        }

        try {
            $templatedEmail->subject($subject);

            $list = api_get_setting('announcement.send_all_emails_to', true);

            if (!empty($list) && isset($list['emails'])) {
                foreach ($list['emails'] as $email) {
                    $templatedEmail->cc($email);
                }
            }

            // Attachment
            if (!empty($data_file)) {
                foreach ($data_file as $file_attach) {
                    if (!empty($file_attach['path']) && !empty($file_attach['filename'])) {
                        $templatedEmail->attachFromPath($file_attach['path'], $file_attach['filename']);
                    }

                    if (!empty($file_attach['stream']) && !empty($file_attach['filename'])) {
                        $templatedEmail->addPart(new DataPart($file_attach['stream'], $file_attach['filename']));
                    }
                }
            }

            $automaticEmailText = '<br />'.get_lang('This is an automatic email message. Please do not reply to it.');

            $params = [
                'mail_header_style' => api_get_setting('mail.mail_header_style'),
                'mail_content_style' => api_get_setting('mail.mail_content_style'),
                'link' => $additionalParameters['link'] ?? '',
                'automatic_email_text' => $automaticEmailText,
                'content' => $body,
            ];

            if (!empty($recipientEmail)) {
                $templatedEmail->to(new Address($recipientEmail, $recipientName));
            }

            if (!empty($replyToEmail)) {
                $templatedEmail->replyTo(new Address($replyToEmail, $replyToName));
            }

            $templatedEmail
                ->htmlTemplate('@ChamiloCore/Mailer/Default/default.html.twig')
                ->context($params)
            ;

            $this->bodyRenderer->render($templatedEmail);
            $this->mailer->send($templatedEmail);

            return true;
        } catch (Exception|TransportExceptionInterface $e) {
            error_log($e->getMessage());

            return false;
        }
    }
}
