<?php

namespace FOS\MessageBundle\SpamDetection;

use FOS\MessageBundle\FormModel\NewThreadMessage;

/**
 * Tells whether or not a new message looks like spam
 *
 * @author Thibault Duplessis <thibault.duplessis@gmail.com>
 */
interface SpamDetectorInterface
{
    /**
     * Tells whether or not a new message looks like spam
     *
     * @param NewThreadMessage $message
     * @return boolean true if it is spam, false otherwise
     */
    function isSpam(NewThreadMessage $message);
}
