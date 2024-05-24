<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ServiceHelper;

use Exception;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\BodyRendererInterface;

final class MailHelper
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly BodyRendererInterface $bodyRenderer,
    ) {}

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

        api_set_noreply_and_from_address_to_mailer(
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
                }
            }

            $noReply = api_get_setting('noreply_email_address');
            $automaticEmailText = '';

            if (!empty($noReply)) {
                $automaticEmailText = '<br />'.get_lang('This is an automatic email message. Please do not reply to it.');
            }

            $params = [
                'mail_header_style' => api_get_setting('mail.mail_header_style'),
                'mail_content_style' => api_get_setting('mail.mail_content_style'),
                'link' => $additionalParameters['link'] ?? '',
                'automatic_email_text' => $automaticEmailText,
                'content' => $body,
                'theme' => api_get_visual_theme(),
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
