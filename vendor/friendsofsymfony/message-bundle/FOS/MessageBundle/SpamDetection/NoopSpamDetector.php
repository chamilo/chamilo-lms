<?php

namespace FOS\MessageBundle\SpamDetection;

use FOS\MessageBundle\FormModel\NewThreadMessage;

class NoopSpamDetector implements SpamDetectorInterface
{
    /**
     * Tells whether or not a new message looks like spam
     *
     * @param NewThreadMessage $message
     * @return boolean true if it is spam, false otherwise
     */
    public function isSpam(NewThreadMessage $message)
    {
        return false;
    }
}
