<?php

namespace FOS\MessageBundle\SpamDetection;

use FOS\AkismetBundle\Akismet\AkismetInterface;
use FOS\MessageBundle\FormModel\NewThreadMessage;
use FOS\MessageBundle\Security\ParticipantProviderInterface;

class AkismetSpamDetector implements SpamDetectorInterface
{
    /**
     * Akismet instance
     *
     * @var AkismetInterface
     */
    protected $akismet;

    /**
     * The participantProvider instance
     *
     * @var ParticipantProviderInterface
     */
    protected $participantProvider;

    public function __construct(AkismetInterface $akismet, ParticipantProviderInterface $participantProvider)
    {
        $this->akismet = $akismet;
        $this->participantProvider = $participantProvider;
    }

    /**
     * Tells whether or not a new message looks like spam
     *
     * @param NewThreadMessage $message
     * @return boolean true if it is spam, false otherwise
     */
    public function isSpam(NewThreadMessage $message)
    {
        return $this->akismet->isSpam(array(
            'comment_author'  => (string) $this->participantProvider->getAuthenticatedParticipant(),
            'comment_content' => $message->getBody()
        ));
    }
}
