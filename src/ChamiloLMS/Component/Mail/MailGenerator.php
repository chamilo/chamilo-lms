<?php

namespace ChamiloLMS\Component\Mail;

class MailGenerator
{
    protected $twig;
    protected $mailer;

    public function __construct(\Twig_Environment $twig, $mailer)
    {
        $this->twig = $twig;
        $this->mailer = $mailer;
    }

    public function getMessage($identifier, $parameters = array())
    {
        /** @var \Twig_Environment $template */
        $template = $this->twig->loadTemplate('default/mail/'.$identifier);

        $subject  = $template->renderBlock('subject', $parameters);
        $bodyHtml = $template->render($parameters);
        /*$bodyText = $template->renderBlock('body_text', $parameters);

        //return $this->mailer
        return \Swift_Message::newInstance()
            ->setSubject($subject)
            ->setBody($bodyText, 'text/plain')
            ->addPart($bodyHtml, 'text/html');*/
        return array(
            'subject' => $subject,
            'body' => $bodyHtml
        );
    }
}
