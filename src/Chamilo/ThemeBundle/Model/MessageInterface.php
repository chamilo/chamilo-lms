<?php
/**
 * MessageInterface.php
 * avanzu-admin
 * Date: 23.02.14.
 */

namespace Chamilo\ThemeBundle\Model;

/**
 * Representation of a displayable message in the theme's messages section.
 */
interface MessageInterface
{
    /**
     * Returns the sender.
     *
     * @return mixed
     */
    public function getFrom();

    /**
     * Returns the sentAt date.
     *
     * @return \DateTime
     */
    public function getSentAt();

    /**
     * Returns the subject.
     *
     * @return string
     */
    public function getSubject();

    /**
     * Returns the unique identifier of this message.
     *
     * @return mixed
     */
    public function getIdentifier();
}
