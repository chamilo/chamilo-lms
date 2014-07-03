<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\CoreBundle\Component\Mail;

/**
 * Class MailGenerator
 * @package ChamiloLMS\CoreBundle\Component\Mail
 */
class MailGenerator
{
    protected $twig;
    protected $mailer;

    /**
     * @param \Twig_Environment $twig
     * @param $mailer
     */
    public function __construct(\Twig_Environment $twig, $mailer)
    {
        $this->twig = $twig;
        $this->mailer = $mailer;
    }

    /**
     * Loads a template file located in default/mail the tpl must declare 3 blocks: subject, html_body and text_body
     * @param $identifier
     * @param array $parameters
     * @return array
     */
    public function getMessage($identifier, $parameters = array())
    {
        /** @var \Twig_Environment $template */
        $template = $this->twig->loadTemplate('default/mail/'.$identifier);

        $subject  = $template->renderBlock('subject', $parameters);
        $htmlBody = $template->renderBlock('html_template', $parameters);
        $textBody = $template->renderBlock('text_template', $parameters);

        return array(
            'subject' => $subject,
            'html_body' => $htmlBody,
            'text_body' => $textBody
        );
    }
}
